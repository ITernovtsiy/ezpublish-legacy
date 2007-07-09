<?php
//
// Definition of eZWorkflow class
//
// Created on: <16-Apr-2002 11:08:14 amos>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ publish
// SOFTWARE RELEASE: 3.10.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2006 eZ systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

//!! eZKernel
//! The class eZWorkflow does
/*!

*/

include_once( "lib/ezdb/classes/ezdb.php" );
include_once( "kernel/classes/ezpersistentobject.php" );
include_once( "kernel/classes/ezworkflowevent.php" );
include_once( 'kernel/classes/ezworkflowgrouplink.php' );

define( "EZ_WORKFLOW_STATUS_NONE", 0 );
define( "EZ_WORKFLOW_STATUS_BUSY", 1 );
define( "EZ_WORKFLOW_STATUS_DONE", 2 );
define( "EZ_WORKFLOW_STATUS_FAILED", 3 );
define( "EZ_WORKFLOW_STATUS_DEFERRED_TO_CRON", 4 );
define( "EZ_WORKFLOW_STATUS_CANCELLED", 5 );
define( "EZ_WORKFLOW_STATUS_FETCH_TEMPLATE", 6 );
define( "EZ_WORKFLOW_STATUS_REDIRECT", 7 );
define( "EZ_WORKFLOW_STATUS_RESET", 8 );
define( "EZ_WORKFLOW_STATUS_WAITING_PARENT", 9 );

class eZWorkflow extends eZPersistentObject
{
    function eZWorkflow( $row )
    {
        $this->eZPersistentObject( $row );
    }

    static function definition()
    {
        return array( "fields" => array( "id" => array( 'name' => 'ID',
                                                        'datatype' => 'integer',
                                                        'default' => 0,
                                                        'required' => true ),
                                         "version" => array( 'name' => "Version",
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ),
                                         "is_enabled" => array( 'name' => "IsEnabled",
                                                                'datatype' => 'integer',
                                                                'default' => 0,
                                                                'required' => true ),
                                         "workflow_type_string" => array( 'name' => "WorkflowTypeString",
                                                                          'datatype' => 'string',
                                                                          'default' => '',
                                                                          'required' => true ),
                                         "name" => array( 'name' => "Name",
                                                          'datatype' => 'string',
                                                          'default' => '',
                                                          'required' => true ),
                                         "creator_id" => array( 'name' => "CreatorID",
                                                                'datatype' => 'integer',
                                                                'default' => 0,
                                                                'required' => true,
                                                                'foreign_class' => 'eZUser',
                                                                'foreign_attribute' => 'contentobject_id',
                                                                'multiplicity' => '1..*' ),
                                         "modifier_id" => array( 'name' => "ModifierID",
                                                                 'datatype' => 'integer',
                                                                 'default' => 0,
                                                                 'required' => true,
                                                                 'foreign_class' => 'eZUser',
                                                                 'foreign_attribute' => 'contentobject_id',
                                                                 'multiplicity' => '1..*' ),
                                         "created" => array( 'name' => "Created",
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ),
                                         "modified" => array( 'name' => "Modified",
                                                              'datatype' => 'integer',
                                                              'default' => 0,
                                                              'required' => true ) ),
                      "keys" => array( "id", "version" ),
                      'function_attributes' => array( 'creator' => 'creator',
                                                      'modifier' => 'modifier',
                                                      'workflow_type' => 'workflowType',
                                                      'event_count' => 'fetchEventCount',
                                                      'ordered_event_list' => 'fetchEvents',
                                                      'ingroup_list' => 'ingroupList',
                                                      'ingroup_id_list' => 'ingroupIDList',
                                                      'group_list' => 'groupList' ),
                      "increment_key" => "id",
                      "class_name" => "eZWorkflow",
                      "sort" => array( "name" => "asc" ),
                      "name" => "ezworkflow" );
    }

    function statusName( $status )
    {
        include_once( 'kernel/workflow/ezworkflowfunctioncollection.php' );
        $statusNames = eZWorkflowFunctionCollection::fetchWorkflowStatuses();
        if ( isset( $statusNames[$status] ) )
            return $statusNames[$status];
        return false;
    }

    function create( $user_id )
    {
        $date_time = time();
        $row = array(
            "id" => null,
            "workflow_type_string" => "group_ezserial",
            "version" => 1,
            "is_enabled" => 1,
            "name" => "",
            "creator_id" => $user_id,
            "modifier_id" => $user_id,
            "created" => $date_time,
            "modified" => $date_time );
        return new eZWorkflow( $row );
    }

    function setIsEnabled( $enabled, $id = false, $version = 0 )
    {
        if ( $id === false )
            $id = $this->attribute( "id" );
        eZPersistentObject::updateObjectList( array(
                                                  "definition" => eZWorkflow::definition(),
                                                  "update_fields" => array( "is_enabled" => ( $enabled ? 1 : 0 ) ),
                                                  "conditions" => array( "id" => $id,
                                                                         "version" => $version )
                                                  )
                                              );
    }

    /*!
     \note Transaction unsafe. If you call several transaction unsafe methods you must enclose
     the calls within a db transaction; thus within db->begin and db->commit.
     */
    function removeWorkflow( $id, $version )
    {
        eZPersistentObject::removeObject( eZWorkflow::definition(),
                                          array("id" => $id,
                                                "version" => $version ) );
    }

    /*!
     \note Transaction unsafe. If you call several transaction unsafe methods you must enclose
     the calls within a db transaction; thus within db->begin and db->commit.
     */
    function remove( $remove_childs = false )
    {
        $db = eZDB::instance();
        $db->begin();
        if ( is_array( $remove_childs ) or $remove_childs )
        {
            if ( is_array( $remove_childs ) )
            {
                $events =& $remove_childs;
                for ( $i = 0; $i < count( $events ); ++$i )
                {
                    $event =& $events[$i];
                    $event->remove();
                }
            }
            else
            {
                eZPersistentObject::removeObject( eZWorkflowEvent::definition(),
                                                  array( "workflow_id" => $this->ID,
                                                         "version" => $this->Version ) );
            }
        }
        eZPersistentObject::remove();
        $db->commit();
    }

    /*!
     \static
     Removes all temporary versions.
     \note Transaction unsafe. If you call several transaction unsafe methods you must enclose
     the calls within a db transaction; thus within db->begin and db->commit.
    */
    function removeTemporary()
    {
        $version = 1;
        $temporaryWorkflows = eZWorkflow::fetchList( $version, null, true );

        $db = eZDB::instance();
        $db->begin();
        foreach ( $temporaryWorkflows as $workflow )
        {
            $workflow->remove( true );
        }
        eZPersistentObject::removeObject( eZWorkflowEvent::definition(),
                                          array( 'version' => $version ) );
        $db->commit();
    }

    /*!
     \note Transaction unsafe. If you call several transaction unsafe methods you must enclose
     the calls within a db transaction; thus within db->begin and db->commit.
     */
    function removeEvents( $events = false, $id = false, $version = false )
    {
        if ( is_array( $events ) )
        {
            $db = eZDB::instance();
            $db->begin();
            for ( $i = 0; $i < count( $events ); ++$i )
            {
                $event =& $events[$i];
                $event->remove();
            }
            $db->commit();
        }
        else
        {
            if ( $version === false )
                $version = $this->Version;
            if ( $id === false )
                $id = $this->ID;
            eZPersistentObject::removeObject( eZWorkflowEvent::definition(),
                                              array( "workflow_id" => $id,
                                                     "version" => $version ) );
        }
    }

    function adjustEventPlacements( &$events )
    {
        if ( !is_array( $events ) )
            return;
        for ( $i = 0; $i < count( $events ); ++$i )
        {
            $event =& $events[$i];
            $event->setAttribute( "placement", $i + 1 );
        }
    }

    /*!
     \note Transaction unsafe. If you call several transaction unsafe methods you must enclose
     the calls within a db transaction; thus within db->begin and db->commit.
     */
    function store( $store_childs = false )
    {

        $db = eZDB::instance();
        $db->begin();
        if ( is_array( $store_childs ) or $store_childs )
        {
            if ( is_array( $store_childs ) )
                $events =& $store_childs;
            else
                $events =& $this->fetchEvents();
            for ( $i = 0; $i < count( $events ); ++$i )
            {
                $event =& $events[$i];
                $event->store();
            }
        }
        eZPersistentObject::store();
        $db->commit();
    }

    function setVersion( $version, $set_childs = false )
    {
        if ( is_array( $set_childs ) or $set_childs )
        {
            if ( is_array( $set_childs ) )
                $events =& $set_childs;
            else
                $events =& $this->fetchEvents();
            for ( $i = 0; $i < count( $events ); ++$i )
            {
                $event =& $events[$i];
                $event->setAttribute( "version", $version );
            }
        }
        eZPersistentObject::setAttribute( "version", $version );
    }

    static function fetch( $id, $asObject = true, $version = 0 )
    {
        return eZPersistentObject::fetchObject( eZWorkflow::definition(),
                                                null,
                                                array( "id" => $id,
                                                       "version" => $version ),
                                                $asObject );
    }

    /*!
      \static
      Fetch workflows based on module, function and connection type

      \param module name
      \param function name
      \param connect type

      \returns array of allowed workflows limited by trigger
    */
    static function &fetchLimited( $moduleName, $functionName, $connectType )
    {
        $workflowArray = eZWorkflow::fetchList();
        $returnArray = array();

        foreach ( array_keys( $workflowArray ) as $key )
        {
            if ( $workflowArray[$key]->isAllowed( $moduleName,
                                                  $functionName,
                                                  $connectType ) )
            {
                $returnArray[] = $workflowArray[$key];
            }
        }

        return $returnArray;
    }

    /*!
      Check if a trigger specified trigger is allowed to use with this workflow

      \param module name
      \param function name
      \param connect type

      \return true if allowed, false if not.
    */
    function isAllowed( $moduleName, $functionName, $connectType )
    {
        $eventArray =& $this->fetchEvents();

        foreach ( array_keys( $eventArray ) as $key )
        {
            $eventType =& $eventArray[$key]->attribute( 'workflow_type' );
            if ( !is_object( $eventType ) or !$eventType->isAllowed( $moduleName, $functionName, $connectType ) )
            {
                return false;
            }
        }

        return true;
    }

    static function fetchList( $version = 0, $enabled = 1, $asObject = true )
    {
        $conds = array( 'version' => $version );
        if ( $enabled !== null )
            $conds['is_enabled'] = $enabled;
        return eZPersistentObject::fetchObjectList( eZWorkflow::definition(),
                                                    null, $conds, null, null,
                                                    $asObject );
    }

    static function &fetchListCount( $version = 0, $enabled = 1 )
    {
        $list = eZPersistentObject::fetchObjectList( eZWorkflow::definition(),
                                                     array(),
                                                     array( 'version' => $version,
                                                            'is_enabled' => $enabled ),
                                                     false,
                                                     null,
                                                     false,
                                                     null,
                                                     array( array( 'operation' => 'count( id )',
                                                                    'name' => 'count' ) ) );
        return $list[0]["count"];
    }

    function fetchEventIndexed( $index )
    {
        $id = $this->ID;
        eZDebugSetting::writeDebug( 'workflow-event', $index, 'index in fetchEventIndexed' );
        $list = eZPersistentObject::fetchObjectList( eZWorkflowEvent::definition(),
                                                      array( "id", "placement" ),
                                                      array( "workflow_id" => $id,
                                                             "version" => 0 ),
                                                      array( "placement" => "asc" ),
                                                      array( "offset" => $index - 1,
                                                             "length" => 1 ),
                                                      false );

        eZDebugSetting::writeDebug( 'workflow-event', $list, "event indexed" );
        if ( count( $list ) > 0 )
            return $list[$index - 1]["id"];
        return null;
    }

    function &fetchEvents( $id = false, $asObject = true, $version = 0 )
    {
        if ( $id === false )
        {
            if ( isset( $this ) and
                 strtolower( get_class( $this ) ) == "ezworkflow" )
            {
                $id = $this->ID;
                $version = $this->Version;
            }
            else
            {
                $retValue = null;
                return $retValue;
            }
        }
        $filteredList = eZWorkflowEvent::fetchFilteredList( array( "workflow_id" => $id,
                                                                    "version" => $version ) );
        return $filteredList;
    }

    function &fetchEventCount( $id = false, $version = 0 )
    {
        if ( $id === false )
        {
            if ( isset( $this ) and
                 strtolower( get_class( $this ) ) == "ezworkflow" )
            {
                $id = $this->ID;
                $version = $this->Version;
            }
            else
            {
                $count = null;
                return $count;
            }
        }
        $list = eZPersistentObject::fetchObjectList( eZWorkflowEvent::definition(),
                                                     array(),
                                                     array( 'version' => $version,
                                                            'workflow_id' => $id ),
                                                     false,
                                                     null,
                                                     false,
                                                     false,
                                                     array( array( 'operation' => 'count( id )',
                                                                   'name' => 'count' ) ) );
        return $list[0]["count"];
    }

    function &creator()
    {
        if ( isset( $this->CreatorID ) and $this->CreatorID )
        {
            include_once( "kernel/classes/datatypes/ezuser/ezuser.php" );
            $user = eZUser::fetch( $this->CreatorID );
        }
        else
            $user = null;
        return $user;
    }

    function &modifier()
    {
        if ( isset( $this->ModifierID ) and $this->ModifierID )
        {
            include_once( "kernel/classes/datatypes/ezuser/ezuser.php" );
            $user = eZUser::fetch( $this->ModifierID );
        }
        else
            $user = null;
        return $user;
    }

    function &ingroupList()
    {
        $this->InGroups = eZWorkflowGroupLink::fetchGroupList( $this->attribute("id"),
                                                               $this->attribute("version"),
                                                               true );
        return $this->InGroups;
    }

    function &ingroupIDList()
    {
        $list = eZWorkflowGroupLink::fetchGroupList( $this->attribute("id"),
                                                     $this->attribute("version"),
                                                     false );

        $this->InGroupIDs = array();
        foreach ( $list as $item )
        {
            $this->InGroupIDs[] = $item['group_id'];
        }
        return $this->InGroupIDs;
    }

    function &groupList()
    {
        $this->AllGroups = eZWorkflowGroup::fetchList();
        return $this->AllGroups;
    }

    function &workflowType()
    {
        include_once( "kernel/classes/ezworkflowtype.php" );
        $workflowType =& eZWorkflowType::createType( $this->WorkflowTypeString );
        return $workflowType;
    }

    /*!
     \note Transaction unsafe. If you call several transaction unsafe methods you must enclose
     the calls within a db transaction; thus within db->begin and db->commit.
     */
    function cleanupWorkFlowProcess()
    {
        if ( isset( $this ) )
        {
            $db = eZDB::instance();
            $workflowID = $this->attribute( 'id' );
            $event_list =& $this->fetchEvents();
            if ( $event_list != null )
            {
                $existEventIDArray = array();
                foreach ( array_keys( $event_list ) as $key )
                {
                    $event =& $event_list[$key];
                    $eventID = $event->attribute( 'id' );
                    $existEventIDArray[] = $eventID;
                }
                $existEventIDString = implode( ',', $existEventIDArray );
                $db->query( "DELETE FROM ezworkflow_process
                                   WHERE workflow_id=$workflowID
                                     AND event_id not in ( $existEventIDString )" );
            }
            else
            {
                $db->query( "DELETE FROM ezworkflow_process
                                   WHERE workflow_id=$workflowID");
            }
        }
    }

    /// \privatesection
    public $ID;
    public $Name;
    public $WorkflowTypeString;
    public $Version;
    public $IsEnabled;
    public $CreatorID;
    public $ModifierID;
    public $Created;
    public $Modified;
    public $InGroups;
    public $InGroupIDs;
    public $AllGroups;
}

?>
