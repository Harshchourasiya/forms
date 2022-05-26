<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Jonas Rittershofer <jotoeri@users.noreply.github.com>
 *
 * @author Jonas Rittershofer <jotoeri@users.noreply.github.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Forms;

// use OCA\MyApp\AppInfo\Application;
// use OCA\MyApp\IMyAppManager;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\IMigrator;
// use OCP\UserMigration\TMigratorBasicVersionHandling;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;
// use Throwable;

class UserMigrator implements IMigrator {
//   use TMigratorBasicVersionHandling;

//   private IMyAppManager $myAppManager;

	/** @var IL10N */
	private $l10n;

//   private const PATH_ROOT = Application::APP_ID . '/';

//   private const PATH_MYAPP_FILE = MyAppMigrator::PATH_ROOT . 'myapp.json';

	public function __construct(IL10N $l10n) {
		$this->l10n = $l10n;
	}

	/**
	 * Export user data
	 *
	 * @throws UserMigrationException
	 */
	public function export(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
//     $output->writeln('Exporting myapp information in ' . MyAppMigrator::PATH_MYAPP_FILE . '…');

//     try {
//       $data = $this->myAppManager->getUserData($user);
//       $exportDestination->addFileContents(MyAppMigrator::PATH_MYAPP_FILE, json_encode($data));
//     } catch (Throwable $e) {
//       throw new UserMigrationException('Could not export myapp information', 0, $e);
//     }
	}

	/**
	 * Import user data
	 *
	 * @throws UserMigrationException
	 */
	public function import(IUser $user, IImportSource $importSource, OutputInterface $output): void {
//     if ($importSource->getMigratorVersion($this->getId()) === null) {
//       $output->writeln('No version for ' . static::class . ', skipping import…');
//       return;
//     }

//     $output->writeln('Importing myapp information from ' . MyAppMigrator::PATH_MYAPP_FILE . '…');

//     $data = json_decode($importSource->getFileContents(MyAppMigrator::PATH_MYAPP_FILE), true, 512, JSON_THROW_ON_ERROR);

//     try {
//       $this->myAppManager->setUserData($user, $data);
//     } catch (Throwable $e) {
//       throw new UserMigrationException('Could not import myapp information', 0, $e);
//     }
	}

	/**
	 * Unique AppID
	 */
	public function getId(): string {
		return 'forms';
	}

	/**
	 * App display name
	 */
	public function getDisplayName(): string {
		return $this->l10n->t('Forms');
	}

	/**
	 * Description for Data-Export
	 */
	public function getDescription(): string {
		return $this->l10n->t('My App information');
	}

	/**
	 * Returns the version of the export format for this migrator
	 */
	public function getVersion(): int {
		return 1;
	}

	/**
	 * Checks whether it is able to import the version, that was stored from getVersion() during export.
	 */
	public function canImport(IImportSource $importSource): bool {
		// Get version of archive to import, currently only import if version 1.
		return $importSource->getMigratorVersion($this->getId()) === 1;
	}
}