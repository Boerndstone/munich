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

    /** Same order of magnitude as gallery photo uploads ({@see PhotoUploadType}). */
    private const REF_IMAGE_MAX_BYTES = 10 * 1024 * 1024;

    /** MIME types supported by {@see TopoWebpImageService::loadImage()}. */
    private const REF_IMAGE_ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

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

        $routesJsonRoute = $request->getLocale() === 'en'
            ? 'frontend_topo_path_suggestion_routes_en'
            : 'frontend_topo_path_suggestion_routes';
        $topoEdit['routesForColorsFetchUrl'] = $this->generateUrl($routesJsonRoute);

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

    /**
     * JSON list of routes (nr, name, grade, colours) for the topo path editor when rock + topo number are chosen
     * without reloading the Mithelfen page (e.g. after picking a reference image file).
     */
    #[Route('/mithelfen/topo-pfade/routes-json', name: 'frontend_topo_path_suggestion_routes', defaults: ['_locale' => 'de'], methods: ['GET'], priority: 350)]
    #[Route('/en/help/topo-paths/routes-json', name: 'frontend_topo_path_suggestion_routes_en', defaults: ['_locale' => 'en'], methods: ['GET'], priority: 350)]
    public function routesForColorsJson(Request $request): JsonResponse
    {
        $rockId = $request->query->getInt('rock', 0);
        $topoNr = $request->query->getInt('topoNr', 0);
        if ($rockId < 1 || $topoNr < 1) {
            return $this->json(['routesForColors' => []]);
        }

        $rock = $this->rockRepository->find($rockId);
        if (!$rock instanceof Rock) {
            return $this->json(['routesForColors' => []]);
        }

        return $this->json([
            'routesForColors' => $this->payloadFactory->buildRoutesForColorsForRockAndTopoNumber($rock, $topoNr),
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
            $size = $file->getSize();
            if ($size === false || $size < 1 || $size > self::REF_IMAGE_MAX_BYTES) {
                return $this->json([
                    'success' => false,
                    'error' => $en
                        ? 'The reference image is too large or empty (max 10 MB).'
                        : 'Das Referenzbild ist zu groß oder leer (max. 10 MB).',
                ], Response::HTTP_BAD_REQUEST);
            }

            $mime = $this->detectMimeType($file->getPathname());
            if (!\in_array($mime, self::REF_IMAGE_ALLOWED_MIMES, true)) {
                return $this->json([
                    'success' => false,
                    'error' => $en
                        ? 'Unsupported image type. Use JPEG, PNG, GIF, or WebP.'
                        : 'Nicht unterstütztes Bildformat. Bitte JPEG, PNG, GIF oder WebP verwenden.',
                ], Response::HTTP_BAD_REQUEST);
            }

            $basename = $this->makePendingImageBasename($rock->getName() ?? 'topo');
            if (!is_dir($this->pendingTopoUploadDir) && !mkdir($this->pendingTopoUploadDir, 0755, true) && !is_dir($this->pendingTopoUploadDir)) {
                return $this->json(['success' => false, 'error' => 'Upload-Verzeichnis fehlt.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            try {
                $this->topoWebpImageService->writeTopoVariantsFromFile(
                    $file->getPathname(),
                    $basename,
                    $this->pendingTopoUploadDir
                );
            } catch (\Throwable) {
                $this->deletePendingTopoImageVariants($basename);
                return $this->json([
                    'success' => false,
                    'error' => $en
                        ? 'The reference image could not be processed. Try another file (JPEG, PNG, GIF, or WebP, max 10 MB).'
                        : 'Das Referenzbild konnte nicht verarbeitet werden. Bitte eine andere Datei versuchen (JPEG, PNG, GIF oder WebP, max. 10 MB).',
                ], Response::HTTP_BAD_REQUEST);
            }

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

    private function detectMimeType(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            return '';
        }
        $finfo = new \finfo(\FILEINFO_MIME_TYPE);
        $mime = @$finfo->file($path);
        if (!\is_string($mime) || $mime === '') {
            return '';
        }

        return strtolower(trim(explode(';', $mime, 2)[0]));
    }

    private function deletePendingTopoImageVariants(string $basename): void
    {
        foreach ([$basename . '.webp', $basename . '@2x.webp'] as $name) {
            $path = $this->pendingTopoUploadDir . '/' . $name;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
