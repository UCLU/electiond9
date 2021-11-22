<?php

namespace Drupal\election;

/**
 * Class ElectionPostConditionChecker.
 */
class ElectionPostConditionChecker {

    public static function checkCondition($account, $condition, $return_reasons = false) {
        switch($condition->condition_type) {
            case 'user_role': 
                return ElectionPostConditionChecker::checkConditionUserRole($account, $return_reasons, $condition->user_role);

            case 'group': 
                return ElectionPostConditionChecker::checkConditionGroup($account, $return_reasons, $condition->group, $condition->group_role);

            case 'account_field_name': 
                return ElectionPostConditionChecker::checkConditionUserField($account, $return_reasons, $condition->user_field_name, $condition->user_field_operator, $condition->user_field_value);
        }
    }

    public static function checkConditionUserRole($account, $return_reasons, $user_role) {
        if(empty($user_role)) {
            return false;
        }
        $hasRole = $account->hasRole($user_role->id());
        if(!$hasRole && $return_reasons) {
            return ['User does not have role "'.$user_role->label().'"'];
        } else {
            return $hasRole;
        }
    }

    public static function checkConditionGroup($account, $return_reasons, $group, $group_role) {
        $isMemberOfGroup = false; // TODO
        if(!$isMemberOfGroup && $return_reasons) {
            return $return_reasons ? ['User not a member of "'.$group->label().'"'] : $isMemberOfGroup;
        } else {            
            $hasGroupRole = false; // TODO
            if(!$hasGroupRole && $return_reasons) {
                return ['User does not have role "'.$group_role->label().'"'];
            } else {
                return $hasGroupRole;
            }
        } 
    }

    public static function checkConditionUserField($account, $return_reasons, $field, $operator, $value) {
        //$field should be a field entity, I guess
        $field_name = ''; // TODO
        if(strpos($field, '/') !== false) {
            // Find it in profile
            $split = str_split($field, '/');
            $profile_name = $split[0];
            $field_name = $split[1];
            $profile = false; // TODO
            $current_value = $profile->get($field_name)->value; // TODO
        } else {
            $current_value = $account->get($field_name)->value; // TODO   
        }
        // Calculate
        $match = false;
        $fail_message = '';
        switch($operator) {
            case '=':
                $match = $current_value == $value;
                $fail_message = 'does not equal';
                break;

            case 'LIKE':
                $match = strpos($current_value, $value) !== false;
                $fail_message = 'does not contain';
                break;

            case '!=':
                $match = $current_value != $value;
                $fail_message = 'is equal to';
                break;
        }

        if(!$match) {
            return $return_reasons ? ['User data "'.$field->label().'" is "'.$current_value."', ".$fail_message.' "'.$value.'"'] : $match; 
        } else {
            return true;
        }
    }
}