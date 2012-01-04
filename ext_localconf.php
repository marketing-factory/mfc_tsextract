<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE == 'BE') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_mfctsextract_task_extract'] = array(
		'extension' => $_EXTKEY,
		'title' => 'LLL:EXT:mfc_tsextract/Resources/Private/Language/locallang_be.xml:extractTask.name',
		'description' => 'LLL:EXT:mfc_tsextract/Resources/Private/Language/locallang_be.xml:extractTask.description',
		'additionalFields' => 'tx_mfctsextract_task_extract_additionalfieldprovider'
	);
}

?>
