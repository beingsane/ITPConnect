<?php
/**
 * @package      ITPrism Components
 * @subpackage   ITPConnect
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2010 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * ITPConnect is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.modellist');

class ItpConnectModelItpcUsers extends JModelList {
    
    /**
     * Context string for the model type.  This is used to handle uniqueness
     * when dealing with the getStoreId() method and caching data structures.
     *
     * @var     string
     * @since   1.6
     */
    protected $context = "com_itpconnect.itpcusers";
        
    /**
     * Constructor.
     *
     * @param   array   An optional associative array of configuration settings.
     * @see     JController
     * @since   1.6
     */
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'name', 'fbuser_id','twuser_id','users_id'
            );
        }

        parent::__construct($config);
    }
    
    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @since   1.6
     */
    protected function populateState($ordering = null, $direction = null){
        // Initialise variables.
        $app = JFactory::getApplication('administrator');

        // Load the filter state.
        $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        
        // List state information.
        parent::populateState('name', 'asc');
    }
    
    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string      $id A prefix for the store id.
     * @return  string      A store id.
     * @since   1.6
     */
    protected function getStoreId($id = ''){
        // Compile the store id.
        $id.= ':' . $this->getState('filter.search');

        return parent::getStoreId($id);
    }
    
	/**
     * Build an SQL query to load the list data.
     *
     * @return  JDatabaseQuery
     * @since   1.6
     */
    protected function getListQuery(){
	    
        // Create a new query object.
        $db     = $this->getDbo();
        $query  = $db->getQuery(true);
        
        $query->select("
            #__itpc_users.*,
            #__users.name
        ");
            
        $query->from("#__itpc_users");
        $query->join("INNER", "#__users ON #__users.id = #__itpc_users.users_id");
		
        // Filter by search in title
        $search = $this->getState('filter.search');
        
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('#__itpc_users.fbuser_id = '.(int)substr($search, 3) . ' OR ' . '#__itpc_users.twuser_id = '.(int)substr($search, 3));
            } else {
                $search = $db->Quote('%'.$db->getEscaped($search, true).'%');
                $query->where('#__users.name LIKE '.$search);
            }
        }
        
        // Add the list ordering clause.
        $orderCol   = $this->state->get('list.ordering');
        $orderDirn  = $this->state->get('list.direction');

        $query->order($db->getEscaped($orderCol.' '.$orderDirn));
        
		return $query;
	}

    /**
     * Delete records from the DB
     *
     * @param array $cids
     * @exception ItpException
     */
    public function delete($cids) {
        
        if(!$cids){
            throw new ItpUserException(JText::_('ITP_ERROR_INVALID_USERS_SELECTED'),500);
        }
        
        // Create a new query object.
        $db     = $this->getDbo();
        
        // Delete categories 
        $query = "
            DELETE  
            FROM 
                 #__itpc_users 
            WHERE   
                 id IN ( ". implode( ',', $cids ) ." )";
        
        $db->setQuery($query);
        
        if(!$db->query()) {
            throw new ItpException($db->getErrorMsg(),500);
        }
            
    }
    
}