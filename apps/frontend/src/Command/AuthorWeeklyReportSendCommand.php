<?php

namespace App\Command;

use App\Entity\Comment;
use App\Repository\UserRepository;
use App\Repository\CommentRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

#[AsCommand(
    name: 'app:author-weekly-report:send',
    description: 'Send weekly reports to authors',
)]
class AuthorWeeklyReportSendCommand extends Command
{

    private $userRepository;
    private $commentRepository;
    private $mailer;

    public function __construct(UserRepository $userRepository, CommentRepository $commentRepository, MailerInterface $mailer)
    {
        parent::__construct(null);
        $this->userRepository = $userRepository;
        $this->commentRepository = $commentRepository;
        $this->mailer = $mailer;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send weekly reports to authors');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $users = $this->userRepository
            ->findAllSubscribedToNewsletter();
        $io->progressStart(count($users));
        foreach ($users as $user) {
            $io->progressAdvance();
            $comments = $this->commentRepository->findAllPublishedComments($user);
            if (count($comments) === 0) {
                continue;
            }
        }

        // dd($comments);

        $email = (new TemplatedEmail())
            ->from(new Address('admin@munichclimbs.de'))
            ->to(new Address('admin@munichclimbs.de'))
            ->subject('Weekly report')
            ->htmlTemplate('emails/comments-user-weekly-report.html.twig')
            ->context([
                'user' => $user,
                'comments' => $comments,
            ]);

        $this->mailer->send($email);

        $io->progressFinish();
        $io->success('Weekly reports were sent to authors!');

        return Command::SUCCESS;
    }
}
