<?php

$extensionPath = t3lib_extMgm::extPath('mfc_tsextract') . 'Classes/';

return array(
	'tx_mfctsextract_task_extract' => $extensionPath . 'Task/Extract.php',
	'tx_mfctsextract_task_extract_additionalfieldprovider' => $extensionPath . 'Task/Extract_additionalfieldprovider.php',

	'tx_mfctsextract_service_extractor' => $extensionPath . 'Service/Extractor.php',
);

?>