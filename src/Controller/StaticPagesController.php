<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Service\FooterAreas;
use App\Service\GradeTranslationService;
use App\Form\ContactFormType;
use App\Repository\AreaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;

class StaticPagesController extends AbstractController
{

    #[Route('/Datenschutz', name: 'datenschutz')]
    public function datenschutz(
        AreaRepository $areaRepository,
        FooterAreas $footerAreas,
    ): Response {
        $areas = $footerAreas->getFooterAreas();
        $sideBar = $areaRepository->sidebarNavigation();

        return $this->render('frontend/datenschutz.html.twig', [
            'areas' => $areas,
            'sideBar' => $sideBar,
        ]);
    }

    #[Route('/Impressum', name: 'impressum')]
    public function impressum(
        AreaRepository $areaRepository,
        FooterAreas $footerAreas,
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $areas = $footerAreas->getFooterAreas();
        $sideBar = $areaRepository->sidebarNavigation();

        $form = $this->createForm(ContactFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                /** @var Contact $contact */
                $contact = $form->getData();

                $entityManager->persist($contact);
                $entityManager->flush();

                // Prepare and send the email
                $email = (new TemplatedEmail())
                    ->from($contact->getEmail()) // Sender's email
                    ->to('admin@munichclimbs.de') // Recipient's email
                    ->subject('Kontaktformular munichclimbs. Betreff:' . $contact->getSubject())
                    ->htmlTemplate('emails/contact.html.twig')
                    ->context([
                        'name' => $contact->getName(),
                        'emailAdress' => $contact->getEmail(),
                        'subject' => $contact->getSubject(),
                        'comment' => $contact->getComment(),
                    ]);

                $mailer->send($email);
                $this->addFlash('success', 'Ihre Nachricht wurde erfolgreich versendet!');

                return $this->redirectToRoute('impressum');
            } else {
                // This block now only executes if the form is submitted but not valid
                $this->addFlash('error', 'Ihre Nachricht konnte nicht versendet werden!');
            }
        }

        return $this->render('frontend/impressum.html.twig', [
            'areas' => $areas,
            'sideBar' => $sideBar,
            'contactForm' => $form->createView(),
        ]);
    }

    #[Route('/Gradvergleich-Bouldern', name: 'bouldering_grade_comparison_redirect', defaults: ['_locale' => 'de'])]
    #[Route('/en/bouldering-grade-comparison', name: 'bouldering_grade_comparison_en_redirect', defaults: ['_locale' => 'en'])]
    public function boulderingGradeComparisonRedirect(Request $request): Response
    {
        $route = str_starts_with($request->getPathInfo(), '/en/')
            ? 'free_climbing_grade_comparison_en'
            : 'free_climbing_grade_comparison';

        return $this->redirectToRoute($route, [], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/Gradvergleich-Freiklettern', name: 'free_climbing_grade_comparison', defaults: ['_locale' => 'de'])]
    #[Route('/en/free-climbing-grade-comparison', name: 'free_climbing_grade_comparison_en', defaults: ['_locale' => 'en'])]
    public function freeClimbingGradeComparison(
        AreaRepository $areaRepository,
        FooterAreas $footerAreas,
    ): Response {
        $areas = $footerAreas->getFooterAreas();
        $sideBar = $areaRepository->sidebarNavigation();

        return $this->render('frontend/free-climbing-grade-comparison.html.twig', [
            'areas' => $areas,
            'sideBar' => $sideBar,
            'comparisonRows' => GradeTranslationService::freeClimbingGradeComparisonTable(),
        ]);
    }
}
