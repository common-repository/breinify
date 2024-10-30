<?php

BreinifyPlugIn::instance()->req('classes/GuiException');

class UiUtility {

    public static function createFormTable($fields, $id, $wrapInForm = true, $class = 'form-table-breinify', $useDashIcon = false) {

        // create the different table rows and the content
        $tableContent = '';
        $showTable = false;
        $currentGroup = null;
        foreach ($fields as $field) {
            $group = UiUtility::def($field, 'group', '');
            $printGroup = $currentGroup === null || $currentGroup !== $group;
            if ($printGroup && '' !== $group) {
                $currentGroup = $group;

                $tableContent .= '<tr class="breinify-tr-group"><td colspan="' . ($useDashIcon ? 3 : 2) . '">' . $currentGroup . '</td></tr>';
            }

            $label = UiUtility::def($field, 'label');
            $type = UiUtility::def($field, 'type');

            if ('separator' === $type) {
                $tableContent .= '<tr class="breinify-separator">';
                $tableContent .= '<td colspan="' . ($useDashIcon ? 3 : 2) . '">&nbsp</td>';
                $tableContent .= '</tr>';

                // stop here there is nothing more to do
                continue;
            }

            // determine if we have to show the table or not
            $showTable = $showTable || $type !== 'hidden';

            $tableContent .= '<tr class="breinify-input-selector-' . $field['name'] . '">';

            if ('checkbox' !== $type) {
                $tableContent .= '<td>' . $label . '</td>';
            }

            $tableContent .= '<td>';
            if ('select' === $type) {
                $tableContent .= '<select name="' . $field['name'] . '">';

                $valueSelector = UiUtility::def($field, 'valueSelector', null);
                $values = (empty($field['values']) || !is_array($field['values']) ? [] : $field['values']);
                foreach ($values as $key => $value) {
                    $selected = UiUtility::def($field, 'value', null) === $key;

                    // get the value
                    if (is_array($value)) {
                        $val = $value[$valueSelector];
                    } else if (is_object($value)) {
                        $val = $value->$valueSelector;
                    } else {
                        $val = $value;
                    }

                    $tableContent .= '<option value="' . $key . '"' . ($selected ? ' selected' : '') . '>' . $val . '</option>';
                }

                $tableContent .= '</select>';
            } else if ('checkbox' === $type) {
                $tableContent .= '<input type="checkbox" name="' . $field['name'] . '"';
                $tableContent .= $field['value'] === true ? ' checked' : '';
                $tableContent .= '/>';
            } else {

                $tableContent .= '<input type="' . $type . '" name="' . $field['name'] . '"';

                // add the options
                $tableContent .= UiUtility::def($field, 'id', '', ' id="', '"');
                $tableContent .= UiUtility::def($field, 'value', '', ' value="', '"');
                $tableContent .= UiUtility::def($field, 'validation', '', ' data-parsley-type="', '"');
                $tableContent .= UiUtility::def($field, 'required', 'data-parsley-required="true"', ' data-parsley-required="', '"');
                $tableContent .= UiUtility::def($field, 'trigger', ' data-parsley-trigger="focusout"', ' data-parsley-trigger="', '"');
                $tableContent .= UiUtility::def($field, 'range', '', ' data-parsley-length="', '"');
                $tableContent .= UiUtility::def($field, 'errorMessage', '', ' data-parsley-error-message="', '"');
                $tableContent .= UiUtility::def($field, 'equalTo', '', ' data-parsley-equalto="#', '"');
                $tableContent .= UiUtility::def($field, 'showError', ' data-parsley-errors-messages-disabled="true"', ' data-parsley-errors-messages-disabled="', '"');

                $tableContent .= '/>';
            }
            $tableContent .= '</td>';

            if ('checkbox' === $type) {
                $tableContent .= '<td data-checkbox-name="' . $field['name'] . '" class="breinify-checkbox-label">' . $label . '</td>';
            }

            if ($useDashIcon) {
                $tableContent .= '<td style="padding-left: 0; padding-right: 0;">';
                $tableContent .= '<span data-ot-show-on="click" data-ot-group="' . $id . '" data-ot-hide-trigger="closeButton" data-ot-target="true" data-ot-fixed="true" data-ot-tip-joint="left" data-ot-style="dark" ';
                $tableContent .= UiUtility::def($field, 'dashicon', '', ' class="dashicons ', '"');
                $tableContent .= UiUtility::def($field, 'dashicon-tooltip', 'data-ot=""', ' data-ot="', '"');
                $tableContent .= '>';
                $tableContent .= '</span>';
                $tableContent .= '</td>';
            }

            $tableContent .= '</tr>';
        }

        // wrap the result in a table
        $table = '';
        $table .= $wrapInForm ? '<form id="' . $id . '" data-parsley-validate ' . ($showTable ? '' : ' style="display:none;"') . '>' : '';
        $table .= '<table class="' . $class . (!$wrapInForm && !$showTable ? ' style="display:none;"' : '') . '">';
        $table .= $tableContent;
        $table .= '</table>';
        $table .= $wrapInForm ? '</form>' : '';

        return $table;
    }

    public static function rndPassword($length = 8) {
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*()_-=+:.?";
        $password = substr(str_shuffle($chars), 0, $length);

        return $password;
    }

    public static function def($field, $name, $def = '', $prefix = '', $suffix = '') {
        return (empty($field[$name]) ? $def : $prefix . $field[$name] . $suffix);
    }

    /**
     * Creates html code to show tabs.
     *
     * @param $tabs array[string]string the tabs to create in key => value.
     * @return string the created html code
     */
    public static function createTabs($tabs) {
        $currentTab = null;
        if (isset($_GET['tab'])) {
            $currentTab = $_GET['tab'];
        } else {
            reset($tabs);
            $currentTab = key($tabs);
        }

        $html = '';
        $html .= '<h2 class="nav-tab-wrapper">';
        foreach ($tabs as $tabKey => $tabCaption) {
            $active = $currentTab == $tabKey ? 'nav-tab-active' : '';
            $html .= '<a class="nav-tab ' . $active . '" href="?page=' . $_GET['page'] . '&tab=' . $tabKey . '">' . $tabCaption . '</a>';
        }
        $html .= '</h2>';

        return ['tab' => $currentTab, 'html' => $html];
    }

    public static function createSubTabs($currentTab, $subTabs) {
        $currentSubTab = null;
        if (isset($_GET['subTab'])) {
            $currentSubTab = $_GET['subTab'];
        } else {
            reset($subTabs);
            $currentSubTab = key($subTabs);
        }

        $html = '';
        $html .= '<ul class="nav-sub">';
        $numItems = count($subTabs);
        $i = 0;
        foreach ($subTabs as $tabKey => $tabCaption) {
            $html .= '<li>';
            $html .= $currentSubTab == $tabKey ? '<span class="nav-subTab">' : '<a class="nav-subTab" href="?page=' . $_GET['page'] . '&tab=' . $currentTab . '&subTab=' . $tabKey . '">';
            $html .= $tabCaption;
            $html .= $currentSubTab == $tabKey ? '</span>' : '</a>';
            $html .= '</li>';
            $html .= ++$i === $numItems ? '' : '<li class="nav-subTab-separator"> | </li>';
        }
        $html .= '</ul>';
        $html .= '<br class="clear">';

        return ['tab' => $currentSubTab, 'html' => $html];
    }

    public static function createMessage($message, $type = 'success') {
        $html = '';
        $html .= '<script>';
        $html .= 'jQuery(document).ready(function () { uiUtility_breinify.showMessage("' . $message . '", "' . $type . '"); });';
        $html .= '</script>';

        return $html;
    }
}