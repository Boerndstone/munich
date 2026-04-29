<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Admin\TopoPathSuggestionCrudController;
use App\Entity\Rock;
use App\Entity\TopoPathSuggestion;
use App\Repository\RockRepository;
use App\Service\TopoPathEditorPayloadFactory;
use App\Service\TopoWebpImageService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class TopoPathSuggestionController extends AbstractController
{
    public const CSRF_INTENT_SEND = 'topo_path_suggestion_send';

    public function __construct(
        private readonly RockRepository $rockRepository,
        private readonly TopoPathEditorPayloadFactory $payloadFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly TopoWebpImageService $topoWebpImageService,
        private readonly SluggerInterface $slugger,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        #[Autowire('%kernel.project_dir%/public/uploads/pending-topo')]
        private readonly string $pendingTopoUploadDir,
    ) {
    }

    #[Route('/mithelfen/topo-pfade', name: 'frontend_topo_path_suggestion', defaults: ['_locale' => 'de'], priority: 350)]
    #[Route('/en/help/topo-paths', name: 'frontend_topo_path_suggestion_en', defaults: ['_locale' => 'en'], priority: 350)]
    public function form(Request $request): Response
    {
        $rock = null;
        $topoNumber = null;
        $rockId = $request->query->getInt('rock', 0);
        $topoNrRaw = $request->query->get('topoNr');
        if ($rockId > 0) {
            $rock = $this->rockRepository->find($rockId);
            if (!$rock instanceof Rock) {
                $rock = null;
            }
        }
        if ($rock !== null && $topoNrRaw !== null && $topoNrRaw !== '') {
            $topoNumber = (int) $topoNrRaw;
            if ($topoNumber < 1) {
                $topoNumber = null;
            }
        }

        $sendRoute = $request->getLocale() === 'en'
            ? 'frontend_topo_path_suggestion_send_en'
            : 'frontend_topo_path_suggestion_send';

        $topoEdit = $this->payloadFactory->buildPublicSuggestionPayload(
            $this->generateUrl($sendRoute),
            $this->generateUrl('index'),
            $rock,
            $topoNumber,
        );

        $rocks = $this->rockRepository->findAllForPublicTopoSelect();

        return $this->render('frontend/topo_path_suggestion.html.twig', [
            'rocks' => $rocks,
            'selectedRockId' => $rock?->getId(),
            'selectedTopoNr' => $topoNumber,
            'topoEdit' => $topoEdit,
            'topoEditJsonBase64' => base64_encode(json_encode($topoEdit, \JSON_UNESCAPED_SLASHES)),
            'sent' => $request->query->getBoolean('sent'),
        ]);
    }

    #[Route('/mithelfen/topo-pfade/send', name: 'frontend_topo_path_suggestion_send', defaults: ['_locale' => 'de'], methods: ['POST'], priority: 350)]
    #[Route('/en/help/topo-paths/send', name: 'frontend_topo_path_suggestion_send_en', defaults: ['_locale' => 'en'], methods: ['POST'], priority: 350)]
    public function send(Request $request): JsonResponse
    {
        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_INTENT_SEND, $token)) {
            return $this->json(['success' => false, 'error' => 'Invalid token.'], Response::HTTP_FORBIDDEN);
        }

        if ($request->request->getString('website') !== '') {
            return $this->json(['success' => false, 'error' => ''], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($request->request->getString('name'));
        $email = trim($request->request->getString('email'));
        $rockId = $request->request->getInt('rockId');
        $topoNr = $request->request->get('topoNr');
        $topoNumber = null;
        if ($topoNr !== null && $topoNr !== '') {
            $topoNumber = (int) $topoNr;
            if ($topoNumber < 1) {
                $topoNumber = null;
            }
        }
        $comment = trim($request->request->getString('comment'));
        $phpLiteral = $request->request->getString('phpLiteral');

        $en = $request->getLocale() === 'en';
        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'success' => false,
                'error' => $en ? 'Please enter your name and a valid email.' : 'Name und gültige E-Mail sind erforderlich.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $rock = $this->rockRepository->find($rockId);
        if (!$rock instanceof Rock) {
            return $this->json([
                'success' => false,
                'error' => $en ? 'Please choose a rock (sector filter above).' : 'Bitte einen Felsen wählen.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($phpLiteral) < 3) {
            return $this->json([
                'success' => false,
                'error' => $en ? 'Please generate or paste path PHP output first.' : 'Bitte Tourenpfade erzeugen oder einfügen (PHP-Ausgabe).',
            ], Response::HTTP_BAD_REQUEST);
        }

        $suggestion = new TopoPathSuggestion();
        $suggestion->setRock($rock);
        $suggestion->setTopoNumber($topoNumber);
        $suggestion->setPathCollection($phpLiteral);
        $suggestion->setUploaderName($name);
        $suggestion->setUploaderEmail($email);
        $suggestion->setComment($comment !== '' ? $comment : null);

        $file = $request->files->get('refImage');
        if ($file instanceof UploadedFile && $file->isValid()) {
            $basename = $this->makePendingImageBasename($rock->getName() ?? 'topo');
            if (!is_dir($this->pendingTopoUploadDir) && !mkdir($this->pendingTopoUploadDir, 0755, true) && !is_dir($this->pendingTopoUploadDir)) {
                return $this->json(['success' => false, 'error' => 'Upload-Verzeichnis fehlt.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $this->topoWebpImageService->writeTopoVariantsFromFile(
                $file->getPathname(),
                $basename,
                $this->pendingTopoUploadDir
            );
            $suggestion->setReferenceImageBasename($basename);
        }

        $this->entityManager->persist($suggestion);
        $this->entityManager->flush();

        $adminListUrl = $this->adminUrlGenerator
            ->setController(TopoPathSuggestionCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        $emailMessage = (new TemplatedEmail())
            ->from(new Address('noreply@munichclimbs.de', 'munichclimbs'))
            ->to('admin@munichclimbs.de')
            ->replyTo(new Address($email, $name))
            ->subject('munichclimbs: Tourenpfad-Vorschlag für „' . ($rock->getName() ?? '') . '“')
            ->htmlTemplate('emails/topo_path_suggestion.html.twig')
            ->context([
                'name' => $name,
                'contactEmail' => $email,
                'rockName' => $rock->getName() ?? '',
                'topoNumber' => $topoNumber,
                'comment' => $comment,
                'pathPreview' => mb_substr($phpLiteral, 0, 2000),
                'suggestionId' => $suggestion->getId(),
                'adminListUrl' => $adminListUrl,
            ]);

        try {
            $this->mailer->send($emailMessage);
        } catch (\Throwable) {
            // submission is stored; email failure should not drop the request
        }

        $backRoute = $request->getLocale() === 'en'
            ? 'frontend_topo_path_suggestion_en'
            : 'frontend_topo_path_suggestion';

        return $this->json([
            'success' => true,
            'redirectUrl' => $this->generateUrl($backRoute, ['sent' => 1]),
        ]);
    }

    private function makePendingImageBasename(string $rockName): string
    {
        $slug = (string) $this->slugger->slug($rockName)->lower();
        if ($slug === '') {
            $slug = 'topo';
        }

        return 'pending-' . $slug . '-' . bin2hex(random_bytes(4));
    }
}
