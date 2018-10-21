<?php

namespace Cloudinary\Cloudinary\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\QuestionHelper;
use Magento\User\Model\UserFactory;
use Magento\User\Model\User;
use Cloudinary\Cloudinary\Helper\Reset;

class ResetAll extends Command
{
    const DESCRIPTION = 'Removes all Cloudinary synchronisation data and ' .
                        'resets all configuration back to the default settings.';
    const WARNING_FORMAT = '<fg=white;bg=red>%s</>';
    const PRE_ACTION_WARNING1 = 'This command will remove all Cloudinary synchronisation data and ' .
                                'reset all configuration back to the default settings.';
    const PRE_ACTION_WARNING2 = 'This process cannot be reversed - we recommend that you backup ' .
                                'your database before proceeding.';
    const PRE_ACTION_MESSAGES = [
        'In order for images to be served from Cloudinary after a reset, you will need to:',
        '* Clear configuration cache',
        '* Reconfigure module',
        '* Enable the module',
        '* Enable auto upload mapping or perform a manual migration'
    ];
    const CONFIRM_MESSAGE = 'Continue with this action? (y/n) ';
    const ADMIN_NAME_REQUEST = 'Please enter your Magento administrator name: ';
    const ADMIN_USER_NOT_FOUND = 'Error - administrator account not found.';
    const ADMIN_PASSWORD_REQUEST = 'Please enter your Magento administrator password: ';
    const ADMIN_PASSWORD_INCORRECT = 'Error - incorrect administrator password.';
    const COMPLETE_MESSAGE1 = 'All Cloudinary module data has been reset.';
    const COMPLETE_MESSAGE2 = 'Please clear your configuration cache to ensure changes take effect.';

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var Reset
     */
    private $resetHelper;


    public function __construct(UserFactory $userFactory, Reset $resetHelper)
    {
        parent::__construct('cloudinary:reset');
        $this->userFactory = $userFactory;
        $this->resetHelper = $resetHelper;
    }

    protected function configure()
    {
        $this->setDescription(self::DESCRIPTION);
    }

    /**
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->displayPreActionMessage($output);

        $helper = $this->getHelper('question');
        if (!$this->confirmActionStart($input, $output, $helper)) {
            return;
        }

        $adminUser = $this->getAdminUser($this->readAdminName($input, $output, $helper));
        if (!$adminUser->getId()) {
            $output->writeln(self::ADMIN_USER_NOT_FOUND);
            return;
        }

        $adminPassword = $this->readAdminPassword($input, $output, $helper);
        if (!$this->authenticate($adminUser, $adminPassword)) {
            $output->writeln(self::ADMIN_PASSWORD_INCORRECT);
            return;
        }

        $this->resetHelper->resetModule();

        $output->writeln(self::COMPLETE_MESSAGE1);
        $output->writeln(self::COMPLETE_MESSAGE2);
    }

    /**
     * @param OutputInterface $output
     */
    private function displayPreActionMessage(OutputInterface $output)
    {
        $output->writeln(sprintf(self::WARNING_FORMAT, self::PRE_ACTION_WARNING1));
        $output->writeln(sprintf(self::WARNING_FORMAT, self::PRE_ACTION_WARNING2));

        array_map(
            function($line) use ($output) {
                $output->writeln(sprintf('<comment>%s</comment>', $line));
            },
            self::PRE_ACTION_MESSAGES
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param QuestionHelper $helper
     * @return bool
     */
    private function confirmActionStart(InputInterface $input, OutputInterface $output, QuestionHelper $helper)
    {
        $confirmationQuestion = new ConfirmationQuestion(self::CONFIRM_MESSAGE, false);

        return (bool)$helper->ask($input, $output, $confirmationQuestion);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param QuestionHelper $helper
     * @return string
     */
    private function readAdminName(InputInterface $input, OutputInterface $output, QuestionHelper $helper)
    {
        $nameQuestion = new Question(self::ADMIN_NAME_REQUEST);
        $response = $helper->ask($input, $output, $nameQuestion);
        return is_string($response) ? $response : '';
    }

    /**
     * @param string $username
     * @return User
     */
    private function getAdminUser($username)
    {
        return $this->userFactory->create()->loadByUsername($username);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param QuestionHelper $helper
     * @return string
     */
    private function readAdminPassword(InputInterface $input, OutputInterface $output, QuestionHelper $helper)
    {
        $passwordQuestion = new Question(self::ADMIN_PASSWORD_REQUEST);
        $passwordQuestion->setHidden(true);
        $response = $helper->ask($input, $output, $passwordQuestion);
        return is_string($response) ? $response : '';
    }

    /**
     * @param User $user
     * @param string $password
     * @return bool
     */
    private function authenticate(User $user, $password)
    {
        try {
            return $user->verifyIdentity($password);
        } catch (\Exception $e) {
        }

        return false;
    }
}
