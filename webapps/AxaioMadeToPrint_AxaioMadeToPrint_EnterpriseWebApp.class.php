<?php

/**
 * Admin web application to configure this plugin. Called by core once opened by admin user
 * through app icon shown at the the Integrations admin page.
 *
 * @package Enterprise
 * @subpackage ServerPlugins
 * @since v8.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR . '/server/utils/htmlclasses/EnterpriseWebApp.class.php';
require_once BASEDIR . '/server/bizclasses/BizWorkflow.class.php';
require_once BASEDIR . '/server/bizclasses/BizPublication.class.php';
require_once dirname(__FILE__) . '/../config.php';
require_once dirname(__FILE__) . '/../AxaioMadeToPrintResource.class.php';

class AxaioMadeToPrint_AxaioMadeToPrint_EnterpriseWebApp extends EnterpriseWebApp {

    public function getTitle() {
        return 'axaio MadeToPrint';
    }

    public function isEmbedded() {
        return true;
    }

    public function getAccessType() {
        return 'admin';
    }

    /**
     * Called by the core server. Builds the HTML body of the web application.
     *
     * @return string HTML
     */
    public function getHtmlBody() {
        return '<iframe src="' . INETROOT . '/config/plugins/AxaioMadeToPrint/webapps/mtpsetup.php" frameborder="0" width="1500" height="800"></iframe>';
    }

    static function buildRowOfCombos($user, $PublicationID, $IssueID, $result, $statuses)
    {
        $idxTabOrder = 3; // begin with 3, two are used on brand selection.

        $strCurrent = '({{LIC_CURRENT}})';
        $strSelectAll = '({{ACT_SELECT_ALL}})';

        $arrLayoutStates = array_merge(
                            array(new State(9999999, '---Layout---')),
                            BizWorkflow::getStates($user, $PublicationID, $IssueID, null, 'Layout'),
                            array(new State(9999998, '---LayoutTemplates---')),
                            BizWorkflow::getStates($user, $PublicationID, $IssueID, null, 'LayoutTemplate'));
        $arrArticleStates = BizWorkflow::getStates($user, $PublicationID, $IssueID, null, 'Article');
        $arrImageStates = BizWorkflow::getStates($user, $PublicationID, $IssueID, null, 'Image');

        $arrUsedStates = array(9999999,9999998);
        if (is_array($statuses)) {
            foreach ($statuses as $usedstat) {
                array_push($arrUsedStates, $usedstat[0]);
            }
        }


        $row = '';

/*RTR*/ $arrayOfState = array_merge(array(new State(0, $strSelectAll)), $arrLayoutStates);
        $lay_Input = self::HTML_BuildSelect('layout', self::getSelectArrayFromStateArray($arrayOfState), $result['state_trigger_layout'], null, $idxTabOrder++, $arrUsedStates);
        $row .= '<tr><td>|</td><td>' . $lay_Input . '</td>';

        $arrayOfState = array_merge(array(new State(0, $strSelectAll)), $arrArticleStates);
        $sta_Input = self::HTML_BuildSelect('state', self::getSelectArrayFromStateArray($arrayOfState), $result['state_trigger_article'], null, $idxTabOrder++);
        $row .= '<td>' . $sta_Input . '</td>';

        $arrayOfState = array_merge(array(new State(0, $strSelectAll)), $arrImageStates);
        $img_Input = self::HTML_BuildSelect('image', self::getSelectArrayFromStateArray($arrayOfState), $result['state_trigger_image'], null, $idxTabOrder++);
        $row .= '<td>' . $img_Input . '</td>';

        $row .= '<td>|</td>';


        $arrayOfState = array_merge(array(new State(0, $strCurrent)), $arrLayoutStates);
        $lay_Input = self::HTML_BuildSelect('layoutafter', self::getSelectArrayFromStateArray($arrayOfState), $result['state_after_layout'], null, $idxTabOrder++);
        $row .= '<td>' . $lay_Input . '</td>';


        $arrayOfState = array_merge(array(new State(0, $strCurrent)), $arrArticleStates);
        $art_Input = self::HTML_BuildSelect('stateafter', self::getSelectArrayFromStateArray($arrayOfState), $result['state_after_article'], null, $idxTabOrder++);
        $row .= '<td>' . $art_Input . '</td>';


        $arrayOfState = array_merge(array(new State(0, $strCurrent)), $arrImageStates);
        $img_Input = self::HTML_BuildSelect('imageafter', self::getSelectArrayFromStateArray($arrayOfState), $result['state_after_image'], null, $idxTabOrder++);
        $row .= '<td>' . $img_Input . '</td>';

        $row .= '<td>|</td>';

        $arrayOfState = array_merge(array(new State(0, $strCurrent)), $arrLayoutStates);
        $lay_Input = self::HTML_BuildSelect('layouterror', self::getSelectArrayFromStateArray($arrayOfState), $result['state_error_layout'], null, $idxTabOrder++);

        $row .= '<td>' . $lay_Input . '</td>';
        $row .= '<td>|</td>';

        if ($result) {
            $row .= '<td><input type="text" value="' . formvar($result['mtp_jobname']) . '" name="mtp_jobname"/></td>';
        } else {
            $row .= '<td><input type="text" value="' . formvar(AXAIO_MTP_JOB_NAME) . '" name="mtp_jobname"/></td>';
        }
        $row .= '<td><input type="checkbox" name="quietmode" value="1"' . ((isset($result['quiet']) && $result['quiet']) ? ' checked="checked"' : '') . '/>';

        $sel_prio = (isset($result['prio']) ? formvar(trim($result['prio'])) : '2');
        $priority = self::HTML_BuildSelect('priority', array_combine(range(4, 0), range(4, 0)), $sel_prio);

        $row .= '<td>' . $priority . '</td>';
        $row .= '<td>|</td></tr>';
        return $row;
    }

    static function getSelectArrayFromStateArray($arrayOfState) {
        $selectArray = array();
        if ($arrayOfState)
            foreach ($arrayOfState as $state) {
                if ($state->Id != -1) { // ingore personal statuses
                    $selectArray[$state->Id] = $state->Name;
                }
            }

        return $selectArray;
    }

    static function HTML_BuildSelect($name, $options, $selectedKey = null, $disabled = false, $tabindex = null, $disabledKey = null) {
        //handle default values
        $tabindex = (isset($tabindex)) ? ' tabindex="' . formvar($tabindex) . '"' : '';
        $disabled = ($disabled == true) ? ' disabled="disabled"' : '';
        $options = (is_array($options)) ? $options : array($options => $options);
        $disabledKey = (is_array($disabledKey)) ? $disabledKey : array();
        $name = formvar($name);

        //start select box
        $select = "<select name='{$name}' {$disabled}{$tabindex}>";

        //handle option elements
        foreach ($options as $opt_key => $opt_val) {
            $selected = ($selectedKey == $opt_key) ? ' selected="selected"' : '';
            $disabled = (in_array($opt_key, $disabledKey)) ? ' disabled="disabled"' : '';
            $opt_key = formvar($opt_key);
            $opt_val = formvar($opt_val);

            $select .= "<option value='{$opt_key}' {$selected}{$disabled}>{$opt_val}</option>";
        }

        //end select box
        $select .= '</select>';
        return $select;
    }

    /**
     * Builds and returns the MtP configuration table in view mode.
     *
     * @param array $configRows The rows from smart_mtp table to draw
     * @param string $user
     * @param int $editlayout The trigger layout status id being edit that must be skipped/ignored
     * @param boolean $show   Wether or not there there not all layout statuses are used and so the Add button needs to be drawn
     */
    static function buildConfigView($configRows, $user, $editlayout, $statuses, &$show) {
        $view = '';
        $laystatearray = array();
        foreach ($configRows as $result) {
            $laytrigger = '';
            $layafter = '';
            $layerror = '';
            $imgtrigger = '';
            $imgafter = '';
            $arttrigger = '';
            $artafter = '';
            $quietmode = '';
            $priority = '';
            $laytriggerid = 0;
            $toview = true;
            $arrayOfState = BizWorkflow::getStates($user, $result['publication_id'], $result['issue_id'], null, 'Article');
            if ($arrayOfState)
                foreach ($arrayOfState as $state) {
                    if ($state->Id != -1) { // ignore personal statuses
                        if ($state->Id == $result['state_trigger_article']) {
                            $arttrigger = formvar($state->Name);
                        }
                        if ($state->Id == $result['state_after_article']) {
                            $artafter = formvar($state->Name);
                        }
                    }
                }
            $arrayOfState = BizWorkflow::getStates($user, $result['publication_id'], $result['issue_id'], null, 'Image');
            if ($arrayOfState)
                foreach ($arrayOfState as $state) {
                    if ($state->Id != -1) { // ignore personal statuses
                        if ($state->Id == $result['state_trigger_image']) {
                            $imgtrigger = formvar($state->Name);
                        }
                        if ($state->Id == $result['state_after_image']) {
                            $imgafter = formvar($state->Name);
                        }
                    }
                }
            $arrayOfState = array_merge(
                            array(new State(9999999, '---Layout---')),
                            BizWorkflow::getStates($user, $result['publication_id'], $result['issue_id'], null, 'Layout'),
                            array(new State(9999998, '---LayoutTemplate---')),
                            BizWorkflow::getStates($user, $result['publication_id'], $result['issue_id'], null, 'LayoutTemplate')
                    );
        
            if ($arrayOfState) {
                foreach ($arrayOfState as $state) {
                    if ($state->Id != -1) { // ignore personal statuses
                        if ($state->Id == $result['state_trigger_layout'] && $state->Id == $editlayout) {
                            $toview = false;
                        }
                        if ($state->Id == $result['state_trigger_layout']) {
                            $laytrigger = formvar($state->Name);
                            $laytriggerid = $state->Id;
                        }
                        if ($state->Id == $result['state_after_layout']) {
                            $layafter = formvar($state->Name);
                        }
                        if ($state->Id == $result['state_error_layout']) {
                            $layerror = formvar($state->Name);
                        }
                        $laystatearray[$state->Id] = 0;
                        foreach ($statuses as $isslayarr) {
                            foreach ($isslayarr as $issue => $lay) {
                                if ($lay == $state->Id && $result['issue_id'] == $issue) {
                                    $laystatearray[$state->Id] = 1;
                                }
                            }
                        }
                    }
                }
            }
            $quietmode = '<input type="checkbox" disabled="disabled" name="quietmode" value="1"' . ((isset($result['quiet']) && $result['quiet']) ? ' checked="checked"' : '') . '/>';
            $priority = '<select name="priority" disabled="disabled">';
            $sel_prio = (isset($result['prio']) ? formvar(trim($result['prio'])) : '2');
            foreach (range(4, 0) as $cur_prio) {
                $priority .= '<option value="' . $cur_prio . '"';
                if ($sel_prio == $cur_prio) {
                    $priority .= ' selected="selected"';
                }
                $priority .= '>' . $cur_prio . '</option>';
            }
            $priority .= '</select>';

            if ($toview) {
                // Draw row of settings for viewing (readonly)
                if (empty($arttrigger)) {
                    $arttrigger = '<font color="#888888">({{ACT_SELECT_ALL}})</font>';
                }
                if (empty($imgtrigger)) {
                    $imgtrigger = '<font color="#888888">({{ACT_SELECT_ALL}})</font>';
                }
                if (empty($layafter)) {
                    $layafter = '<font color="#888888">({{LIC_CURRENT}})</font>';
                }
                if (empty($artafter)) {
                    $artafter = '<font color="#888888">({{LIC_CURRENT}})</font>';
                }
                if (empty($imgafter)) {
                    $imgafter = '<font color="#888888">({{LIC_CURRENT}})</font>';
                }
                if (empty($layerror)) {
                    $layerror = '<font color="#888888">({{LIC_CURRENT}})</font>';
                }
                $mtpText = trim($result['mtp_jobname']) == '' ? '<font color="#888888">(' . AXAIO_MTP_JOB_NAME . ')</font>' : formvar(trim($result['mtp_jobname']));
                $view .= '<tr bgcolor="#DDDDDD"><td>|</td><td>' . $laytrigger . '</td><td>' . $arttrigger . '</td><td>' . $imgtrigger . '</td><td>|</td>'
                        . '	<td>' . $layafter . '</td><td>' . $artafter . '</td><td>' . $imgafter . '</td><td>|</td><td>' . $layerror . '</td><td>|</td><td>' . $mtpText . '</td><td>' . $quietmode . '</td><td>' . $priority . '</td><td>|</td>'
                        . '	<td><a href="?act_del=true&pub=' . $result['publication_id'] . '&iss=' . $result['issue_id'] . '&dellayout=' . $laytriggerid . '">'
                        . '		<img src="../../../images/remov_16.gif" border="0"  onClick="return confirm(\'{{ACT_QUIT_LOSING_CHANGES}}\');" title="{{ACT_DEL}}" /></a>&nbsp;'
                        . '<a href="?act_edit=true&pub=' . $result['publication_id'] . '&iss=' . $result['issue_id'] . '&editlayout=' . $laytriggerid . '">'
                        . '		<img src="../../../images/prefs_16.gif" border="0" title="{{ACT_EDIT}}" /></a></td>'
                        . '</tr>';
            }
        }
        $show = false;
        foreach ($laystatearray as $present) {
            if ($present == 0) {
                $show = true;
            }
        }
        $show = $show || empty($laystatearray);
        return $view;
    }

    /**
     * Detects if there are issues configured that are n longer supported.
     * This is when the issue has Overrule Publication flag disabled or when issue is in non-print channel.
     * In other terms, only print-issues and overrule-issues are configurable (and so they are listed at issue combo).
     * Print-issues with overrule flag disabled are configurable at publication level ("Select All" item).
     * This all implies that you can configure MtP only for entire workflow definitions at SCE.
     *
     * @param array $badIssueIds Returns the issues that are not supported (key=id, value=name)
     * @return string Error report (html) when there are unsupported (bad) issues found. Empty when all ok.
     */
    static function detectBadIssues(&$badIssueIds)
    {
        $error = '';
        $dbDriver = DBDriverFactory::gen();
        $mtpTab = DBPREFIX.'axaio_mtp_trigger';
        $pubTab = $dbDriver->tablename('publications');
        $issTab = $dbDriver->tablename('issues');
        $chnTab = $dbDriver->tablename('channels');

        $sql = "SELECT iss.`name`, iss.`id`, pub.`publication` FROM $mtpTab mtp " .
                "LEFT JOIN $issTab iss ON ( iss.`id` = mtp.`issue_id` ) " .
                "LEFT JOIN $chnTab chn ON ( chn.`id` = iss.`channelid` ) " .
                "LEFT JOIN $pubTab pub ON ( pub.`id` = chn.`publicationid` ) " .
                "WHERE iss.`overrulepub` <> 'on' OR chn.`type` <> 'print' ";
        $sth = $dbDriver->query($sql);
        $configRows = array();
        while (($result = $dbDriver->fetch($sth))) {
            $configRows[] = $result;
        }
        if (count($configRows) > 0) {
            $error = 'There are configurations made for unsupported issue types. ' .
                    'Issues for non-print channels or issues with disabled Overrule Publication flag are not supported.' .
                    'Please remove the MadeToPrint configurations for the following issues since they disturb the production process: <ul>';
            foreach ($configRows as $row) {
                $error .= '<li>- ' . formvar($row['name']) . ' (Publication: ' . formvar($row['publication']) . ')' . '</li>';
                $badIssueIds[$row['id']] = $row['name'];
            }
            $error .= '</ul>';
        }
        return $error;
    }

}
