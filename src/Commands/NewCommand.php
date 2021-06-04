<?php

namespace AwStudio\Installer\Commands;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new Laravel application')
            ->addArgument('repository', InputArgument::REQUIRED);
        //->addOption('dev', null, InputOption::VALUE_NONE, 'Installs the latest "development" release')
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface   $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repo = $input->getArgument('repository');
        $org = Str::before($repo, '/');
        $name = Str::after($repo, '/');

        if (is_null($org) || $org == '' || ! str_contains($repo, '/')) {
            throw new InvalidArgumentException("Invalid repository [{$repo}].");
        }

        $this->installLaravel($name);
        $this->setupGit($repo, $name);

        return 0;
    }

    /**
     * Install laravel.
     *
     * @return void
     */
    public function installLaravel($name)
    {
        exec("laravel new {$name} -f");
    }

    /**
     * Setup git.
     *
     * @param  string $repo
     * @return void
     */
    public function setupGit($repo, $name)
    {
        exec(implode(' && ', [
            "cd {$name}",
            'rm README.md',
            'touch README.md',
            'echo "# '.$name.'" >> README.md',
            'git init',
            'git add README.md',
            'git commit -m "hello world"',
            'git branch -M main',
            "gh repo create {$repo} --private -y",
            'git push -u origin HEAD',
            'jq -n \'{"required_status_checks":{
                "strict": true,
                "contexts": []
            },"required_pull_request_reviews":{
                "dismissal_restrictions": {},
                "dismiss_stale_reviews": false,
                "require_code_owner_reviews": false,
                "required_approving_review_count": 1
            },"enforce_admins":true,"restrictions":null}\' | gh api repos/'.$repo.'/branches/main/protection -X PUT -H "Accept:application/vnd.github.luke-cage-preview+json" --input -',
            'git checkout -b wip/setup',
            'git add .',
            'git commit -m "install laravel"',
            'git push --set-upstream origin wip/setup',
            'gh pr create -B main -H wip/setup --title "Install Laravel" --body "This pr installs the [Laravel](https://laravel.com) framework."',
        ]));
    }
}
