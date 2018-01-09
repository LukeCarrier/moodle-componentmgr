<?php

/**
 * Moodle component manager.
 *
 * @author Luke Carrier <luke@carrier.im>
 * @copyright 2016 Luke Carrier
 * @license GPL-3.0+
 */

namespace ComponentManager\Task;

use ComponentManager\HttpClient;
use ComponentManager\Moodle;
use ComponentManager\MoodleApi;
use ComponentManager\MoodleVersion;
use ComponentManager\Platform\Platform;
use ComponentManager\Project\Project;
use ComponentManager\Step\BuildComponentsStep;
use ComponentManager\Step\CommitProjectLockFileStep;
use ComponentManager\Step\InstallComponentsStep;
use ComponentManager\Step\ObtainMoodleSourceStep;
use ComponentManager\Step\PackageStep;
use ComponentManager\Step\RemoveTempDirectoriesStep;
use ComponentManager\Step\ResolveComponentVersionsStep;
use ComponentManager\Step\ResolveMoodleVersionStep;
use ComponentManager\Step\ValidateProjectStep;
use ComponentManager\Step\VerifyPackageRepositoriesCachedStep;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Package task.
 *
 * Performs all of the operations performed by the installation task, but
 * performs a clean Moodle installation before installing components and
 * finishes builds by packaging up the resulting source into an archive.
 */
class PackageTask extends InstallTask implements Task {
    /**
     * Moodle version.
     *
     * @var MoodleVersion
     */
    protected $moodleVersion;

    /**
     * Initialiser.
     *
     * @param MoodleApi  $moodleApi
     * @param Project    $project
     * @param string     $moodleArchive
     * @param string     $moodleDestination
     * @param Filesystem $filesystem
     * @param HttpClient $httpClient
     * @param Moodle     $moodle
     * @param string     $packageFormat
     * @param string     $packageDestination
     * @param integer    $attempts
     */
    public function __construct(MoodleApi $moodleApi, Project $project,
                                $moodleArchive, $moodleDestination,
                                Platform $platform, Filesystem $filesystem,
                                HttpClient $httpClient, Moodle $moodle,
                                $packageFormat, $packageDestination,
                                $attempts) {
        /* Because we're reordering the installation steps, we don't want to
         * call InstallTask's constructor. */
        AbstractTask::__construct();

        $this->resolvedComponentVersions = [];

        $this->addStep(new ValidateProjectStep($project));
        $this->addStep(new VerifyPackageRepositoriesCachedStep(
                $project->getPackageRepositories()));
        $this->addStep(new ResolveMoodleVersionStep(
                $moodleApi, $project->getProjectFile()->getMoodleVersion()));
        $this->addStep(new ResolveComponentVersionsStep($project));
        $this->addStep(new ObtainMoodleSourceStep(
                $httpClient, $moodleArchive, dirname($moodleDestination)));
        $this->addStep(new InstallComponentsStep(
                $project, $moodle, $platform, $filesystem, $attempts));
        $this->addStep(new BuildComponentsStep(
                $moodle, $platform, $filesystem));
        $this->addStep(new CommitProjectLockFileStep(
                $project->getProjectLockFile()));
        $this->addStep(new PackageStep(
                $project, $moodleDestination, $packageFormat,
                $packageDestination));
        $this->addStep(new RemoveTempDirectoriesStep($platform));
    }

    /**
     * Get the Moodle version.
     *
     * @return MoodleVersion
     */
    public function getMoodleVersion() {
        return $this->moodleVersion;
    }

    /**
     * Set the Moodle version.
     *
     * @param MoodleVersion $moodleVersion
     *
     * @return void
     */
    public function setMoodleVersion(MoodleVersion $moodleVersion) {
        $this->moodleVersion = $moodleVersion;
    }
}
