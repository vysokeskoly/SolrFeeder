<?php

require __DIR__ . '/vendor/vysokeskoly/deb-build/src/autoload.php';

use Robo\Common\ResourceExistenceChecker;
use Robo\Tasks;
use VysokeSkoly\Build\ComposerParserTrait;
use VysokeSkoly\Build\FpmCheckerTrait;
use VysokeSkoly\Build\PackageVersionerTrait;
use VysokeSkoly\Build\Task\LoadTasksTrait;

class RoboFile extends Tasks
{
    use ComposerParserTrait;
    use FpmCheckerTrait;
    use PackageVersionerTrait;
    use ResourceExistenceChecker;
    use LoadTasksTrait;

    const INSTALL_DIR = 'srv/www/SolrFeeder';

    /**
     * Build deb package. It is expected the Composer packages were installed using `--no-dev`.
     *
     * @param array $options
     * @return int
     */
    public function buildDeb($options = ['dev-build' => false])
    {
        $this->stopOnFail();

        $isDevBuild = (bool) $options['dev-build'];

        if (!$this->checkFpmIsInstalled()) {
            return 1;
        }

        $packageName = 'vysokeskoly-solr-feeder';
        $packageVersion = $this->assemblePackageVersion($isDevBuild);
        $versionIteration = $this->assembleVersionIteration();
        $composer = $this->parseComposer();

        $temporaryBuildDir = $this->_tmpDir();
        $buildRootDir = $temporaryBuildDir . '/root';
        $appInstallDir = $buildRootDir . '/' . self::INSTALL_DIR;

        // Create basic filesystem structure
        $this->taskFilesystemStack()
            ->mkdir($appInstallDir)
            ->mkdir($appInstallDir . '/bin')
            ->mkdir($appInstallDir . '/etc')
            ->mkdir($appInstallDir . '/var')
            ->run();

        // Generate postinst script
        $postinstResult = $this->taskPostinst($packageName, $appInstallDir, self::INSTALL_DIR)
            ->args([
                'automat', // runtime files owner
                'automat', // runtime files group
            ])
            ->run();

        $postinstPath = $postinstResult['path'];

        // Copy required directories
        foreach (['bin', 'src', 'vendor'] as $directoryToCopy) {
            $this->_copyDir(__DIR__ . '/' . $directoryToCopy, $appInstallDir . '/' . $directoryToCopy);
        }

        // Copy required files
        foreach (['robo.phar', 'composer.json', 'composer.lock', 'RoboFile.php'] as $fileToCopy) {
            $this->_copy(__DIR__ . '/' . $fileToCopy, $appInstallDir . '/' . $fileToCopy);
        }

        // Generate buildinfo.xml
        $this->taskBuildinfo($appInstallDir . '/buildinfo.xml')
            ->appName($packageName)
            ->version($packageVersion . '-' . $versionIteration)
            ->run();

        // Even when packages are installed using `composer install --no-dev`, they often contains unneeded files.
        $vendorDirectoriesToDelete = [
            'lstrojny/functional-php/tests',
            'mf/callback-parser/Tests',
            'mf/collection-php/Tests',
            'mf/type-validator/Tests',
            'solarium/solarium/tests',
        ];

        // Clean unwanted vendor directories
        foreach ($vendorDirectoriesToDelete as $vendorDirectoryToDelete) {
            $this->_deleteDir($appInstallDir . '/vendor/' . $vendorDirectoryToDelete);
        }

        $this->taskFilesystemHelper()
            ->dir($appInstallDir)
            ->removeDirsRecursively('Tests', 'vendor/symfony')// Remove Tests files from Symfony itself
            ->run();

        $this->taskExec('fpm')
            ->args(['--description', $composer['description']])// description for `apt search`
            ->args(['-s', 'dir'])// source type
            ->args(['-t', 'deb'])// output type
            ->args(['--name', $packageName])// package name
            ->args(['--vendor', 'VysokeSkoly'])
            ->args(['--architecture', 'all'])
            ->args(['--version', $packageVersion])
            ->args(['--iteration', $versionIteration])
            ->args(['-C', $buildRootDir])// change directory to here before searching for files
            ->args(['--depends', 'php-common'])
            ->args(['--depends', 'php-cli'])
            ->args(['--after-install', $postinstPath])
            // Files placed in /etc wouldn't be overridden on package update without following flag:
            ->arg('--deb-no-default-config-files')
            ->arg('.')
            ->run();

        $this->io()->success('Done');

        return 0;
    }

    /**
     * Run post-installation tasks for deb package
     *
     * @param string $runtimeFilesOwner name of the user to whom should the files created on runtime belong to
     * @param string $runtimeFilesGroup name of the group to whom should the files created on runtime belong to
     */
    public function installDebPostinst($runtimeFilesOwner, $runtimeFilesGroup)
    {
        $this->stopOnFail();
        $installDir = '/' . self::INSTALL_DIR;

        // Setup rights recursively
        $directoriesToChmod = [
            $installDir,
        ];
        foreach ($directoriesToChmod as $directoryToChmod) {
            $this->taskFilesystemHelper()
                ->dir($directoryToChmod)
                ->chmodRecursivelyWritableByUserReadableByOthers()
                ->run();
        }

        // Make var/ directory (containing cache and logs) recursively owned and writable for given user
        $directories = ['bin', 'etc', 'var'];
        foreach ($directories as $directory) {
            $directory = $installDir . '/' . $directory;
            $this->taskFilesystemStack()
                ->chown($directory, $runtimeFilesOwner, true)
                ->chgrp($directory, $runtimeFilesGroup, true)// group is the same as user
                ->chmod($directory, 0755)// writable by user
                ->run();

            $this->taskFilesystemHelper()
                ->dir($directory)
                ->chmodRecursivelyWritableByUserReadableByOthers()
                ->run();
        }
    }
}