<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Sebastian Fischer <sf@marketing-factory.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * @author Sebastian Fischer <sf@marketing-factory.de>
 */
class tx_mfctsextract_service_extractor {
	/**
	 * @var integer
	 */
	protected $startPid = -1;

	/**
	 * @var t3lib_TCEmain
	 */
	protected $tceMain;

	/**
	 * @var array
	 */
	protected $pageTypoScriptConfig = array();

	/**
	 * @var string
	 */
	protected $defaultExtractPath = 'fileadmin/admin/main/templates_ts/';

	/**
	 * @var string
	 */
	protected $defaultFilenamePattern = '{type}_{pid}_{uid}_{title}.ts';

	/**
	 * @var string
	 */
	protected $path = '';

	/**
	 * @var string
	 */
	protected $relPath = '';

	/**
	 * @var string
	 */
	protected $masterSetupFile = 'master_setup.ts';

	/**
	 * @var string
	 */
	protected $masterConstantsFile = 'master_constants.ts';

	/**
	 * @var resource filehandle of master setup typoscript file
	 */
	protected $masterSetup;

	/**
	 * @var resource filehandle of master constants typoscript file
	 */
	protected $masterConstants;


	/**
	 * @param integer $startPid
	 * @return tx_mfctsextract_service_extractor
	 */
	public function setStartPid($startPid) {
		$this->startPid = $startPid;
		return $this;
	}

	/**
	 * @return void
	 */
	public function extractTypoScript() {
		$this->fetchPageTypoScriptConfig();
		$this->instantiateTCEmain();

		if ($this->openMasterTypoScriptFile()) {
			$page = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'pid, uid, title',
				'pages',
				'deleted = 0 AND uid = ' . $this->startPid
			);
			$this->runRecursive(current($page));
			$this->closeMasterTypoScriptFile();
		}
	}


	/**
	 * @return void
	 */
	protected function fetchPageTypoScriptConfig() {
		$this->pageTypoScriptConfig = t3lib_BEfunc::getModTSconfig($this->startPid, 'mod.web_txmfctsextract');

		if (!isset($this->pageTypoScriptConfig['properties']['path']) || $this->pageTypoScriptConfig['properties']['path'] == '') {
			$this->pageTypoScriptConfig['properties']['path'] = $this->defaultExtractPath;
		}

		if (!isset($this->pageTypoScriptConfig['filenamepattern']) || $this->pageTypoScriptConfig['properties']['filenamepattern'] == '') {
			$this->pageTypoScriptConfig['properties']['filenamepattern'] = $this->defaultFilenamePattern;
		}
		$this->path = t3lib_div::getFileAbsFileName($this->pageTypoScriptConfig['properties']['path'], FALSE);
		$this->relPath = str_replace(PATH_site, '', t3lib_div::getFileAbsFileName($this->pageTypoScriptConfig['properties']['path']));
	}

	/**
	 * @return void
	 */
	protected function instantiateTCEmain() {
		$this->tceMain = t3lib_div::makeInstance('t3lib_TCEmain');
	}

	/**
	 * @return boolean
	 */
	protected function openMasterTypoScriptFile() {
		$result = FALSE;

		if (file_exists($this->path . $this->masterSetupFile)) {
			throw(new Exception('Typoscript master already exists so no new run possible'));
		} else {
			try {
					$this->masterSetup = fopen($this->path . $this->masterSetupFile, 'a+');
					$this->masterConstants = fopen($this->path . $this->masterConstantsFile, 'a+');

					$startMessage = "\n\n\n# Extraction on " . date('Y-m-d', $GLOBALS['EXEC_TIME']) . "\n########################################\n";

					fwrite($this->masterSetup, $startMessage);
					fwrite($this->masterConstants, $startMessage);

					$result = TRUE;
			} catch (Exception $e) { }
		}

		return $result;
	}

	/**
	 * @return void
	 */
	protected function closeMasterTypoScriptFile() {
		try {
			fclose($this->masterSetup);
			fclose($this->masterConstants);

			t3lib_div::fixPermissions($this->path . $this->masterSetupFile);
			t3lib_div::fixPermissions($this->path . $this->masterConstantsFile);

			if (file_exists($this->path . '_includeStaticWarnlist.txt')) {
				t3lib_div::fixPermissions($this->path . '_includeStaticWarnlist.txt');

				$message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					'Some include static files were found so please look into ' . $this->path . '_includeStaticWarnlist.txt',
					'Template with include static found',
					t3lib_FlashMessage::WARNING
				);
				t3lib_FlashMessageQueue::addMessage($message);
			}
		} catch (Exception $e) { }
	}


	/**
	 * @param integer|array $parent
	 * @return void
	 */
	protected function runRecursive($parentPage) {
		$this->workOnTypoScriptRecordsOfPage($parentPage);

		$pages = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'pid, uid, title',
			'pages',
			'deleted = 0 AND pid = ' . $parentPage['uid']
		);

		while ($page = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($pages)) {
			$this->runRecursive($page);
		}
	}

	/**
	 * @param array $page
	 */
	protected function workOnTypoScriptRecordsOfPage($page) {
		$templates = $this->fetchTemplates('pid = ' . (int) $page['uid']);

		foreach ($templates as $template) {
			$this->fetchConnectedTemplates($template['basedOn'], $page);
			$this->writeTemplateSetupAndConstants($template, $page);

			$this->hideTemplate($template, $page);
		}
	}

	/**
	 * @param string $uidList
	 * @param array $page
	 * @return void
	 */
	protected function fetchConnectedTemplates($uidList, $page) {
		if ($uidList) {
			$uids = t3lib_div::intExplode(',', $uidList, TRUE);

			$templates = $this->fetchTemplates('uid IN (' . $uidList . ')');

			foreach ($uids as $uid) {
				$template = $templates[$uid];
				$this->fetchConnectedTemplates($template['basedOn'], $page);
				$this->writeTemplateSetupAndConstants($template, $page);

				$this->hideTemplate($template, $page);
			}
		}
	}


	/**
	 * @param array $template
	 * @param array $page
	 * @return void
	 */
	protected function writeTemplateSetupAndConstants($template, $page) {
		$this->writeSetupToFile($template, $page);
		$this->writeConstantsToFile($template, $page);
	}

	/**
	 * @param array $template
	 * @param array $page
	 * @return void
	 */
	protected function writeSetupToFile($template, $page) {
		if ($template['config']) {
			$filename = $this->getFilename('setup', $template);
			$content = $template['config'];

			$this->writeToFile($filename, $content, $page);
			$this->addInclude($this->masterSetup, $filename, $template, $page);
		}
	}

	/**
	 * @param array $template
	 * @param array $page
	 * @return void
	 */
	protected function writeConstantsToFile($template, $page) {
		if ($template['constants']) {
			$filename = $this->getFilename('constants', $template);
			$content = $template['constants'];

			$this->writeToFile($filename, $content, $page);
			$this->addInclude($this->masterConstants, $filename, $template, $page);
		}
	}

	/**
	 * @param resource $fileHandle
	 * @param string $filename
	 * @param array $template
	 * @param array $page
	 */
	protected function addInclude($fileHandle, $filename, $template, $page) {
		$content = "\n\n" . '# Include typoscript [' . $template['uid'] . '] ' . $template['title'] . ' on page [' .
			$page['uid'] . '] ' . $page['title'] . "\n" .
			'[PIDinRootline = ' . $page['uid'] . ']' . "\n" .
			'<INCLUDE_TYPOSCRIPT:source="FILE:' . $this->relPath . $filename . '">' . "\n" . '[end]' . "\n";

		fseek($fileHandle, 0, SEEK_END);
		fwrite($fileHandle, $content, strlen($content));
	}

	/**
	 * @param string $filename
	 * @param string $content
	 * @param array $page
	 */
	protected function writeToFile($filename, $content, $page) {
		$content =
			'# Extracted on ' . date('Y-m-d', $GLOBALS['EXEC_TIME']) . "\n" .
			'# was on page: [' . $page['uid'] . '] ' . $page['title'] . "\n\n" . $content;

		try {
			file_put_contents($this->path . $filename, $content);

			t3lib_div::fixPermissions($this->path . $filename);
		} catch (Exception $e) { }
	}

	/**
	 * @param string $type
	 * @param array $template
	 * @return string
	 */
	protected function getFilename($type, $template) {
		$title = str_replace(array('_', '.', ' '), '-', preg_replace('/[^a-z0-9\s_\.]/i', '', strtolower($template['title'])));

		return str_replace(
			array('{type}', '{pid}', '{uid}', '{title}'),
			array($type, $template['pid'], $template['uid'], $title),
			$this->pageTypoScriptConfig['properties']['filenamepattern']
		);
	}


	/**
	 * @param string $andWhere
	 * @return array
	 */
	protected function fetchTemplates($andWhere) {
			// config is setup field
			// constants is constants field
			// basedOn are included sys_template records as comma separated id list
			// include_static_file typoscript files from extensions
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid, pid, title,
			config, constants,
			include_static_file, basedOn',
			'sys_template',
			'hidden = 0 AND deleted = 0 AND ' . $andWhere,
			'',
			'sorting',
			'',
			'uid'
		);
	}

	/**
	 * @param array $template
	 * @param array $page
	 * @return integer
	 */
	protected function hideTemplate($template, $page) {
		$hide = 0;

		if ($template['include_static_file'] == '' && $template['clear'] == 0) {
			$hide = 1;
		} else {
			if ($fileHandle = fopen($this->path . '_includeStaticWarnlist.txt', 'a+')) {
				$string = 'Template "' . $template['title'] . ' (' . $template['uid'] . ')" on page "' . $page['title'] . ' (' . $page['uid'] . ')" includes following statics: ' .
					"\n" . $template['include_static_file'] . "\n\n";

				fwrite($fileHandle, $string, strlen($string));
				fclose($fileHandle);
			}
		}

		$data = array();
		$data['sys_template'][$template['uid']] = array(
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'hidden' => $hide,
		);

		$this->tceMain->start($data, array());
		$this->tceMain->process_datamap();
	}
}

?>