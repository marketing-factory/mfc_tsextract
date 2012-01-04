<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Sebastian Fischer <sebastian.fischer@e-netconsulting.com>
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
 *
 * @author		Sebastian Fischer <sebastian.fischer@e-netconsulting.com>
 * @package		TYPO3
 * @subpackage	enetrssimporter
 *
 */
class tx_mfctsextract_task_extract_additionalfieldprovider implements tx_scheduler_AdditionalFieldProvider {

	/**
	 * This method is used to define new fields for adding or editing a task
	 * In this case, it adds an email field
	 *
	 * @param	array					$taskInfo: reference to the array containing the info used in the add/edit form
	 * @param	object					$task: when editing, reference to the current task object. Null when adding.
	 * @param	tx_scheduler_Module		$parentObject: reference to the calling object (Scheduler's BE module)
	 * @return	array					Array containg all the information pertaining to the additional fields
	 *									The array is multidimensional, keyed to the task class name and each field's id
	 *									For each field it provides an associative sub-array with the following:
	 *										['code']		=> The HTML code for the field
	 *										['label']		=> The label of the field (possibly localized)
	 *										['cshKey']		=> The CSH key for the field
	 *										['cshLabel']	=> The code of the CSH label
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {
		$additionalFields = array();

			// Initialize sourcepage field value
		if (empty($taskInfo['tsextract_startpid'])) {
			if ($parentObject->CMD == 'add') {
			} elseif ($parentObject->CMD == 'edit') {
					// In case of edit, and editing a test task, set to internal value if not data was submitted already
				$taskInfo['tsextract_startpid'] = $task->startpid;
			} else {
					// Otherwise set an empty value, as it will not be used anyway
				$taskInfo['tsextract_startpid'] = '';
			}
		}

			// Write the code for the field
		$fieldID = 'task_startpid';
		$fieldCode = '<select name="tx_scheduler[tsextract_startpid]" id="' . $fieldID .
			'">' . $this->getStartPidOptions($taskInfo['tsextract_startpid']) .
			'</select>';
		$additionalFields[$fieldID] = array(
			'code'     => $fieldCode,
			'label'    => 'LLL:EXT:mfc_tsextract/Resources/Private/Language/locallang_be.xml:extractTask.startpid',
		);

		return $additionalFields;
	}
	
	/**
	 * @param	array
	 * @return	string
	 */
	protected function getStartPidOptions($selectedOptions) {
		$table = 'pages';
		$enableFields = t3lib_BEfunc::BEenableFields($table).t3lib_BEfunc::deleteClause($table);

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$table,
			'TSconfig != "" ' . $enableFields
		);

		$options = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$ModTSconfig = t3lib_BEfunc::getModTSconfig($row['uid'], 'mod.web_txmfctsextract');

			if ($ModTSconfig['properties']['start']) {
				if (in_array($row['uid'], (array) $selectedOptions)) {
					$selected = ' selected="selected"';
				} else {
					$selected = '';
				}
				$options[] = '<option value="' . $row['uid'] .  '"' . $selected . '>[' . $row['uid'] . '] ' . $row['title'] . '</option>';
			}
		}

		return implode('', $options);
	}

	/**
	 * This method checks any additional data that is relevant to the specific task
	 * If the task class is not relevant, the method is expected to return true
	 *
	 * @param	array					$submittedData: reference to the array containing the data submitted by the user
	 * @param	tx_scheduler_Module		$parentObject: reference to the calling object (Scheduler's BE module)
	 * @return	boolean					True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $parentObject) {
		return true;
	}

	/**
	 * This method is used to save any additional input into the current task object
	 * if the task class matches
	 *
	 * @param	array				$submittedData: array containing the data submitted by the user
	 * @param	tx_scheduler_Task	$task: reference to the current task object
	 * @return	void
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		$task->startpid = $submittedData['tsextract_startpid'];
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mfc_tsextract/Classes/Task/Extract_additionalfieldprovider.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mfc_tsextract/Classes/Task/Extract_additionalfieldprovider.php']);
}

?>