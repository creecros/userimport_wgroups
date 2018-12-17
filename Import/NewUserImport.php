<?php

namespace Kanboard\Plugin\ImportWithGroup\Import;

use Kanboard\Model\UserModel;
use Kanboard\Model\GroupModel;
use Kanboard\Model\GroupMemberModel;
use SimpleValidator\Validator;
use SimpleValidator\Validators;
use Kanboard\Core\Security\Role;
use Kanboard\Core\Base;
use Kanboard\Core\Csv;

/**
 * User Import with Group
 *
 * @package  import
 * @author   Craig Crosby
 */
class NewUserImport extends Base
{
    /**
     * Number of successful import
     *
     * @access public
     * @var integer
     */
    public $counter = 0;

    /**
     * Get mapping between CSV header and SQL columns
     *
     * @access public
     * @return array
     */
    public function getColumnMapping()
    {
        return array(
            'username'         => 'Username',
            'password'         => 'Password',
            'email'            => 'Email',
            'name'             => 'Full Name',
            'is_admin'         => 'Administrator',
            'is_manager'       => 'Manager',
            'is_ldap_user'     => 'Remote User',
            'groupname'        => 'Group Name',
        );
    }

    /**
     * Import a single row
     *
     * @access public
     * @param  array   $row
     * @param  integer $line_number
     */
    public function import(array $row, $line_number)
    {
        $row = $this->prepare($row);
        
        $group_id = $row['group_id'];
        unset($row['group_id']);
        
        if ($this->validateCreation($row)) {
            $user_id = $this->userModel->create($row);
            if ($user_id !== false) {
                if ($group_id != 0) { $this->groupMemberModel->addUser($group_id, $user_id); }
                $this->logger->debug('UserImport: imported successfully line '.$line_number);
                $this->counter++;
            } else {
                $this->logger->error('UserImport: creation error at line '.$line_number);
            }
        } else {
            $this->logger->error('UserImport: validation error at line '.$line_number);
        }
    }

    /**
     * Format row before validation
     *
     * @access public
     * @param  array   $row
     * @return array
     */
    public function prepare(array $row)
    {
        $row['username'] = strtolower($row['username']);

        foreach (array('is_admin', 'is_manager', 'is_ldap_user') as $field) {
            $row[$field] = Csv::getBooleanValue($row[$field]);
        }

        if ($row['is_admin'] == 1) {
            $row['role'] = Role::APP_ADMIN;
        } elseif ($row['is_manager'] == 1) {
            $row['role'] = Role::APP_MANAGER;
        } else {
            $row['role'] = Role::APP_USER;
        }
        
        $group_id = $this->getOrCreateGroupByName($row['groupname'], '');
        unset($row['groupname']);
        
        $row['group_id'] = $group_id;
        
        unset($row['is_admin']);
        unset($row['is_manager']);

        $this->helper->model->removeEmptyFields($row, array('password', 'email', 'name'));

        return $row;
    }

    /**
     * Validate user creation
     *
     * @access public
     * @param  array   $values
     * @return boolean
     */
    public function validateCreation(array $values)
    {
        $v = new Validator($values, array(
            new Validators\MaxLength('username', t('The maximum length is %d characters', 255), 255),
            new Validators\Unique('username', t('The username must be unique'), $this->db->getConnection(), UserModel::TABLE, 'id'),
            new Validators\MinLength('password', t('The minimum length is %d characters', 6), 6),
            new Validators\Email('email', t('Email address invalid')),
            new Validators\Integer('is_ldap_user', t('This value must be an integer')),
            new Validators\MaxLength('groupname', t('The maximum length is %d characters', 191), 191),
        ));

        return $v->execute();
    }
    
    public function addUserToGroup($user_id) 
    {
    
    }
    
    /**
     * Get groupId from group name and create the group if not found
     *
     * @access public
     * @param  string $name
     * @param  string $external_id
     * @return bool|integer
     */
    public function getOrCreateGroupByName($name, $external_id ='')
    {
        if (!empty($name)) {
            $group_id = $this->db->table(GroupModel::TABLE)->eq('name', $name)->findOneColumn('id');
            if (empty($group_id)) {
                $group_id = $this->groupModel->create($name, $external_id);
            }
            return $group_id;
        } else {
            return 0;
        }
    }
}
