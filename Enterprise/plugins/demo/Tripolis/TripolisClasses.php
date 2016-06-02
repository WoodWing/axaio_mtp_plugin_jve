<?php
/****************************************************************************
   Copyright 2009 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

/**
 * 
 * 
 * @package
 * @copyright
 */
class ApiResponse {
  /* int */
  public $responseCode;
  /* string */
  public $responseDescription;
  /* string */
  public $message;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class ArticleInfo {
  /* string */
  public $articleId;
  /* string */
  public $label;
  /* string */
  public $name;
  /* string */
  public $articleTypeDefinitionName;
  /* string */
  public $modifiedBy;
  /* dateTime */
  public $modifiedDate;
  /* string */
  public $tags;
  /* KeyValuePair */
  public $articleFields;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class Tripolis_Attachment {
  /* string */
  public $fileName;
  /* base64Binary */
  public $data;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class AuthInfo {
  /* string */
  public $client;
  /* string */
  public $username;
  /* string */
  public $password;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class BouncedContact {
  /* ContactInfo */
  public $contactInfo;
  /* string */
  public $emailAddress;
  /* dateTime */
  public $receivedBounce;
  /* string */
  public $bounceCode;
  /* int */
  public $bounceCategoryId;
  /* string */
  public $bounceCategoryDescription;
  /* string */
  public $bounceReason;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class ContactDatabaseInfo {
  /* string */
  public $contactDatabaseId;
  /* string */
  public $label;
  /* string */
  public $name;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class ContactInfo {
  /* string */
  public $contactId;
  /* KeyValuePair */
  public $keyValuePairs;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class ContactWithGroupMembershipsInfo {
  /* ContactInfo */
  public $contactInfo;
  /* GroupMembershipInfo */
  public $groupMembershipInfos;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class DatabaseField {
  /* string */
  public $label;
  /* string */
  public $name;
  /* string */
  public $defaultValue;
  /* string */
  public $type;
  /* boolean */
  public $key;
  /* boolean */
  public $mandatory;
  /* int */
  public $minimumLength;
  /* int */
  public $maximumLength;
  /* KeyValuePair */
  public $picklistItems;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class DatabaseGroup {
  /* string */
  public $label;
  /* string */
  public $name;
  /* string */
  public $type;
  /* string */
  public $parentGroupName;
}

/**
 * DialogueService class
 * 
 *  
 * 
 * @author    {author}
 * @copyright {copyright}
 * @package   {package}
 */
class DialogueService extends SoapClient {

  public function DialogueService($wsdl = "https://api.tripolis.com/soap/1.5/no-mtom/DialogueService?wsdl", $options = array()) {
    parent::__construct($wsdl, $options);
  }

  /**
   *  
   *
   * @param getClicksByMailing $parameters
   * @return getClicksByMailingResponse
   */
  public function getClicksByMailing(getClicksByMailing $parameters) {
    return $this->__call('getClicksByMailing', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getLastMailingSnapshot $parameters
   * @return getLastMailingSnapshotResponse
   */
  public function getLastMailingSnapshot(getLastMailingSnapshot $parameters) {
    return $this->__call('getLastMailingSnapshot', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getDatabaseFields $parameters
   * @return getDatabaseFieldsResponse
   */
  public function getDatabaseFields(getDatabaseFields $parameters) {
    return $this->__call('getDatabaseFields', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getBouncesByMailing $parameters
   * @return getBouncesByMailingResponse
   */
  public function getBouncesByMailing(getBouncesByMailing $parameters) {
    return $this->__call('getBouncesByMailing', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param upsertImage $parameters
   * @return upsertImageResponse
   */
  public function upsertImage(upsertImage $parameters) {
    return $this->__call('upsertImage', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param createDatabaseField $parameters
   * @return createDatabaseFieldResponse
   */
  public function createDatabaseField(createDatabaseField $parameters) {
    return $this->__call('createDatabaseField', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getContactDatabases $parameters
   * @return getContactDatabasesResponse
   */
  public function getContactDatabases(getContactDatabases $parameters) {
    return $this->__call('getContactDatabases', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getNewsletterTypes $parameters
   * @return getNewsletterTypesResponse
   */
  public function getNewsletterTypes(getNewsletterTypes $parameters) {
    return $this->__call('getNewsletterTypes', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getOpensByMailing $parameters
   * @return getOpensByMailingResponse
   */
  public function getOpensByMailing(getOpensByMailing $parameters) {
    return $this->__call('getOpensByMailing', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getNotSendByMailing $parameters
   * @return getNotSendByMailingResponse
   */
  public function getNotSendByMailing(getNotSendByMailing $parameters) {
    return $this->__call('getNotSendByMailing', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param deleteContactGroup $parameters
   * @return deleteContactGroupResponse
   */
  public function deleteContactGroup(deleteContactGroup $parameters) {
    return $this->__call('deleteContactGroup', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param upsertNewsletter $parameters
   * @return upsertNewsletterResponse
   */
  public function upsertNewsletter(upsertNewsletter $parameters) {
    return $this->__call('upsertNewsletter', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getArticle $parameters
   * @return getArticleResponse
   */
  public function getArticle(getArticle $parameters) {
    return $this->__call('getArticle', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getNewsletterSections $parameters
   * @return getNewsletterSectionsResponse
   */
  public function getNewsletterSections(getNewsletterSections $parameters) {
    return $this->__call('getNewsletterSections', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getContacts $parameters
   * @return getContactsResponse
   */
  public function getContacts(getContacts $parameters) {
    return $this->__call('getContacts', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param upsertContactWithNewsletterResponse $parameters
   * @return upsertContactWithNewsletterResponseResponse
   */
  public function upsertContactWithNewsletterResponse(upsertContactWithNewsletterResponse $parameters) {
    return $this->__call('upsertContactWithNewsletterResponse', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param assignContactsToGroup $parameters
   * @return assignContactsToGroupResponse
   */
  public function assignContactsToGroup(assignContactsToGroup $parameters) {
    return $this->__call('assignContactsToGroup', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param upsertContact $parameters
   * @return upsertContactResponse
   */
  public function upsertContact(upsertContact $parameters) {
    return $this->__call('upsertContact', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param previewNewsletter $parameters
   * @return previewNewsletterResponse
   */
  public function previewNewsletter(previewNewsletter $parameters) {
    return $this->__call('previewNewsletter', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param upsertArticle $parameters
   * @return upsertArticleResponse
   */
  public function upsertArticle(upsertArticle $parameters) {
    return $this->__call('upsertArticle', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param removeContactGroupMembership $parameters
   * @return removeContactGroupMembershipResponse
   */
  public function removeContactGroupMembership(removeContactGroupMembership $parameters) {
    return $this->__call('removeContactGroupMembership', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param exportContactDatabaseMailBlacklist $parameters
   * @return exportContactDatabaseMailBlacklistResponse
   */
  public function exportContactDatabaseMailBlacklist(exportContactDatabaseMailBlacklist $parameters) {
    return $this->__call('exportContactDatabaseMailBlacklist', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param importContacts $parameters
   * @return importContactsResponse
   */
  public function importContacts(importContacts $parameters) {
    return $this->__call('importContacts', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param deleteContact $parameters
   * @return deleteContactResponse
   */
  public function deleteContact(deleteContact $parameters) {
    return $this->__call('deleteContact', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param createDatabase $parameters
   * @return createDatabaseResponse
   */
  public function createDatabase(createDatabase $parameters) {
    return $this->__call('createDatabase', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getWorkspaces $parameters
   * @return getWorkspacesResponse
   */
  public function getWorkspaces(getWorkspaces $parameters) {
    return $this->__call('getWorkspaces', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getImportStatus $parameters
   * @return getImportStatusResponse
   */
  public function getImportStatus(getImportStatus $parameters) {
    return $this->__call('getImportStatus', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getMailings $parameters
   * @return getMailingsResponse
   */
  public function getMailings(getMailings $parameters) {
    return $this->__call('getMailings', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param exportClientMailBlacklist $parameters
   * @return exportClientMailBlacklistResponse
   */
  public function exportClientMailBlacklist(exportClientMailBlacklist $parameters) {
    return $this->__call('exportClientMailBlacklist', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getDirectEmailTypes $parameters
   * @return getDirectEmailTypesResponse
   */
  public function getDirectEmailTypes(getDirectEmailTypes $parameters) {
    return $this->__call('getDirectEmailTypes', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param createContactGroup $parameters
   * @return createContactGroupResponse
   */
  public function createContactGroup(createContactGroup $parameters) {
    return $this->__call('createContactGroup', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getContactsWithGroupMemberships $parameters
   * @return getContactsWithGroupMembershipsResponse
   */
  public function getContactsWithGroupMemberships(getContactsWithGroupMemberships $parameters) {
    return $this->__call('getContactsWithGroupMemberships', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param exportContacts $parameters
   * @return exportContactsResponse
   */
  public function exportContacts(exportContacts $parameters) {
    return $this->__call('exportContacts', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getDatabaseGroups $parameters
   * @return getDatabaseGroupsResponse
   */
  public function getDatabaseGroups(getDatabaseGroups $parameters) {
    return $this->__call('getDatabaseGroups', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getMailingSnapshot $parameters
   * @return getMailingSnapshotResponse
   */
  public function getMailingSnapshot(getMailingSnapshot $parameters) {
    return $this->__call('getMailingSnapshot', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param assignArticleToNewsletter $parameters
   * @return assignArticleToNewsletterResponse
   */
  public function assignArticleToNewsletter(assignArticleToNewsletter $parameters) {
    return $this->__call('assignArticleToNewsletter', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param upsertDirectMail $parameters
   * @return upsertDirectMailResponse
   */
  public function upsertDirectMail(upsertDirectMail $parameters) {
    return $this->__call('upsertDirectMail', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getSummaryReport $parameters
   * @return getSummaryReportResponse
   */
  public function getSummaryReport(getSummaryReport $parameters) {
    return $this->__call('getSummaryReport', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param scheduleMailing $parameters
   * @return scheduleMailingResponse
   */
  public function scheduleMailing(scheduleMailing $parameters) {
    return $this->__call('scheduleMailing', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getNotSentByMailing $parameters
   * @return getNotSentByMailingResponse
   */
  public function getNotSentByMailing(getNotSentByMailing $parameters) {
    return $this->__call('getNotSentByMailing', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getMaxValueOfContactField $parameters
   * @return getMaxValueOfContactFieldResponse
   */
  public function getMaxValueOfContactField(getMaxValueOfContactField $parameters) {
    return $this->__call('getMaxValueOfContactField', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param tellAFriend $parameters
   * @return tellAFriendResponse
   */
  public function tellAFriend(tellAFriend $parameters) {
    return $this->__call('tellAFriend', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param previewDirectEmail $parameters
   * @return previewDirectEmailResponse
   */
  public function previewDirectEmail(previewDirectEmail $parameters) {
    return $this->__call('previewDirectEmail', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param searchContacts $parameters
   * @return searchContactsResponse
   */
  public function searchContacts(searchContacts $parameters) {
    return $this->__call('searchContacts', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

  /**
   *  
   *
   * @param getMailing $parameters
   * @return getMailingResponse
   */
  public function getMailing(getMailing $parameters) {
    return $this->__call('getMailing', array(
            new SoapParam($parameters, 'parameters')
      ),
      array(
            'uri' => 'http://api.tripolis.com/',
            'soapaction' => ''
           )
      );
  }

}

/**
 * 
 * 
 * @package
 * @copyright
 */
class DirectEmail {
  /* string */
  public $directEmailId;
  /* string */
  public $label;
  /* string */
  public $name;
  /* string */
  public $subject;
  /* string */
  public $description;
  /* string */
  public $fromName;
  /* string */
  public $fromAddress;
  /* string */
  public $replyTo;
  /* string */
  public $html;
  /* string */
  public $text;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class DirectEmailType {
  /* string */
  public $label;
  /* string */
  public $name;
  /* string */
  public $fromName;
  /* string */
  public $fromAddress;
  /* string */
  public $replyTo;
  /* string */
  public $htmlUrl;
  /* string */
  public $textUrl;
  /* string */
  public $encoding;
  /* string */
  public $emailFieldName;
  /* boolean */
  public $editorEnabled;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class GroupMembershipInfo {
  /* string */
  public $groupLabel;
  /* string */
  public $groupName;
  /* boolean */
  public $subscribed;
  /* boolean */
  public $confirmed;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class ImageInfo {
  /* string */
  public $imageId;
  /* Attachment */
  public $attachment;
  /* string */
  public $name;
  /* string */
  public $label;
  /* string */
  public $description;
  /* string */
  public $tags;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class ImportStatus {
  /* boolean */
  public $running;
  /* dateTime */
  public $startTime;
  /* dateTime */
  public $endTime;
  /* string */
  public $importMode;
  /* int */
  public $created;
  /* int */
  public $updated;
  /* int */
  public $addedToGroup;
  /* int */
  public $removedFromGroup;
  /* int */
  public $numberOfErrors;
  /* string */
  public $importedBy;
  /* string */
  public $importErrors;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class KeyValuePair {
  /* string */
  public $key;
  /* string */
  public $value;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class Mailing {
  /* string */
  public $mailingId;
  /* string */
  public $mailingName;
  /* string */
  public $subject;
  /* dateTime */
  public $startTime;
  /* dateTime */
  public $endTime;
  /* string */
  public $campaignName;
  /* string */
  public $databaseName;
  /* string */
  public $groupName;
  /* string */
  public $smartgroupName;
  /* int */
  public $mailsPerHour;
  /* long */
  public $mailInterval;
  /* int */
  public $sampleNumber;
  /* string */
  public $status;
  /* string */
  public $error;
  /* boolean */
  public $isRemoved;
  /* string */
  public $createdBy;
  /* int */
  public $requestedNumberOfSend;
  /* int */
  public $numberOfSend;
  /* int */
  public $numberOfSkipped;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class MailingSnapshot {
  /* string */
  public $html;
  /* string */
  public $text;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class MailingSummaryReport {
  /* int */
  public $queued;
  /* double */
  public $queuedPct;
  /* int */
  public $hardBounces;
  /* double */
  public $hardBouncesPct;
  /* int */
  public $softBounces;
  /* double */
  public $softBouncesPct;
  /* int */
  public $totalOpens;
  /* int */
  public $uniqueOpens;
  /* int */
  public $totalClicks;
  /* int */
  public $uniqueClicks;
  /* int */
  public $ctrDelivered;
  /* int */
  public $ctrOpened;
  /* int */
  public $hotmailComplaints;
  /* double */
  public $hotmailComplaintsPct;
  /* int */
  public $aolComplaints;
  /* double */
  public $aolComplaintsPct;
  /* int */
  public $outblazeComplaints;
  /* double */
  public $outblazeComplaintsPct;
  /* int */
  public $totalComplaints;
  /* double */
  public $totalComplaintsPct;
  /* string */
  public $replyTo;
  /* string */
  public $fromAddress;
  /* string */
  public $fromName;
  /* string */
  public $mailingType;
  /* string */
  public $typeName;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class NewsletterInfo {
  /* string */
  public $newsletterId;
  /* string */
  public $label;
  /* string */
  public $name;
  /* string */
  public $subject;
  /* string */
  public $fromAddress;
  /* string */
  public $fromName;
  /* string */
  public $newsletterTypeName;
  /* dateTime */
  public $modifiedDate;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class NewsletterPreview {
  /* string */
  public $htmlContent;
  /* string */
  public $textContent;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class NewsletterSectionInfo {
  /* string */
  public $newsletterSectionId;
  /* string */
  public $label;
  /* string */
  public $name;
  /* boolean */
  public $rssActive;
  /* string */
  public $rssUrl;
  /* int */
  public $numberOfRssArticles;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class NewsletterType {
  /* string */
  public $label;
  /* string */
  public $name;
  /* string */
  public $fromName;
  /* string */
  public $fromAddress;
  /* string */
  public $replyTo;
  /* string */
  public $encoding;
  /* string */
  public $emailFieldName;
  /* string */
  public $defaultSubject;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class NotSendContact {
  /* ContactInfo */
  public $contactInfo;
  /* string */
  public $notSendReason;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class NotSendContacts {
  /* string */
  public $jobId;
  /* NotSendContact */
  public $notSendContacts;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class NotSentContact {
  /* ContactInfo */
  public $contactInfo;
  /* NotSentReason */
  public $notSentReason;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class NotSentContacts {
  /* string */
  public $jobId;
  /* NotSentContact */
  public $notSentContacts;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class NotSentReason {
  /* string */
  public $toAddress;
  /* string */
  public $type;
  /* string */
  public $description;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class Open {
  /* string */
  public $contactId;
  /* string */
  public $jobId;
  /* string */
  public $ip;
  /* string */
  public $browser;
  /* dateTime */
  public $timeStamp;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class SearchParameter {
  /* string */
  public $operator;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class TimeRange {
  /* dateTime */
  public $startDate;
  /* dateTime */
  public $endDate;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class Workspace {
  /* string */
  public $workspaceId;
  /* string */
  public $contactDatabaseId;
  /* string */
  public $label;
  /* string */
  public $name;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class assignArticleToNewsletter {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $workspaceName;
  /* string */
  public $newsletterName;
  /* string */
  public $sectionName;
  /* string */
  public $articleName;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class assignArticleToNewsletterResponse {
  /* string */
  public $response;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class assignContactsToGroup {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* string */
  public $groupName;
  /* string */
  public $refName;
  /* string */
  public $refIp;
  /* string */
  public $contactIds;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class assignContactsToGroupResponse {
  /* KeyValuePair */
  public $contactStatus;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class clickedUrl {
  /* string */
  public $browser;
  /* string */
  public $contactId;
  /* string */
  public $ipAddress;
  /* string */
  public $jobId;
  /* typeOfLink */
  public $linkType;
  /* string */
  public $OS;
  /* dateTime */
  public $timeStamp;
  /* string */
  public $url;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class createContactGroup {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* DatabaseGroup */
  public $contactGroup;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class createContactGroupResponse {
  /* string */
  public $response;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class createDatabase {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* string */
  public $databaseLabel;
  /* DatabaseField */
  public $databaseField;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class createDatabaseField {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* DatabaseField */
  public $databaseField;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class createDatabaseFieldResponse {
  /* string */
  public $response;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class createDatabaseResponse {
  /* string */
  public $response;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class deleteContact {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* string */
  public $contactId;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class deleteContactGroup {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* string */
  public $groupName;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class deleteContactGroupResponse {
  /* ApiResponse */
  public $response;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class deleteContactResponse {
  /* string */
  public $response;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class exportClientMailBlacklist {
  /* AuthInfo */
  public $authInfo;
  /* TimeRange */
  public $timeRange;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class exportClientMailBlacklistResponse {
  /* Attachment */
  public $response;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class exportContactDatabaseMailBlacklist {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* TimeRange */
  public $timeRange;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class exportContactDatabaseMailBlacklistResponse {
  /* Attachment */
  public $response;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class exportContacts {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* string */
  public $groupName;
  /* string */
  public $databaseFieldName;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class exportContactsResponse {
  /* Attachment */
  public $exportContacts;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getArticle {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $articleId;
  /* string */
  public $contactId;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getArticleResponse {
  /* ArticleInfo */
  public $article;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getBouncesByMailing {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $mailingId;
  /* TimeRange */
  public $timeRange;
  /* string */
  public $databaseFieldNames;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getBouncesByMailingResponse {
  /* BouncedContact */
  public $bouncedContact;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getClicksByMailing {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $mailingId;
  /* TimeRange */
  public $timeRange;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getClicksByMailingResponse {
  /* clickedUrl */
  public $clickedUrl;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getContactDatabases {
  /* AuthInfo */
  public $authInfo;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getContactDatabasesResponse {
  /* ContactDatabaseInfo */
  public $contactDatabaseInfo;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getContacts {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* string */
  public $contactId;
  /* KeyValuePair */
  public $getParameter;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getContactsResponse {
  /* ContactInfo */
  public $contactInfo;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getContactsWithGroupMemberships {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* string */
  public $contactId;
  /* KeyValuePair */
  public $getParameter;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getContactsWithGroupMembershipsResponse {
  /* ContactWithGroupMembershipsInfo */
  public $membershipInfo;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getDatabaseFields {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getDatabaseFieldsResponse {
  /* DatabaseField */
  public $databaseField;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getDatabaseGroups {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getDatabaseGroupsResponse {
  /* DatabaseGroup */
  public $databaseGroup;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getDirectEmailTypes {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $workspaceName;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getDirectEmailTypesResponse {
  /* DirectEmailType */
  public $directEmailType;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getImportStatus {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $importId;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getImportStatusResponse {
  /* ImportStatus */
  public $importStatus;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getLastMailingSnapshot {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* string */
  public $workspaceName;
  /* string */
  public $groupName;
  /* string */
  public $sampleContactId;
  /* string */
  public $mailingType;
  /* string */
  public $typeDefinitionName;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getLastMailingSnapshotResponse {
  /* MailingSnapshot */
  public $mailingSnapshot;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getMailing {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $mailingId;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getMailingResponse {
  /* Mailing */
  public $mailing;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getMailingSnapshot {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $mailingId;
  /* string */
  public $sampleContactId;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getMailingSnapshotResponse {
  /* MailingSnapshot */
  public $mailingSnapshot;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getMailings {
  /* AuthInfo */
  public $authInfo;
  /* TimeRange */
  public $timeRange;
  /* string */
  public $databaseName;
  /* string */
  public $groupName;
  /* string */
  public $analyticsIntegrationPartner;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getMailingsResponse {
  /* Mailing */
  public $mailing;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getMaxValueOfContactField {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* string */
  public $fieldName;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getMaxValueOfContactFieldResponse {
  /* int */
  public $maxValue;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getNewsletterSections {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $workspaceName;
  /* string */
  public $newsletterTypeName;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getNewsletterSectionsResponse {
  /* NewsletterSectionInfo */
  public $newsletterSectionInfo;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getNewsletterTypes {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $workspaceName;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getNewsletterTypesResponse {
  /* NewsletterType */
  public $newsletterType;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getNotSendByMailing {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $mailingId;
  /* string */
  public $databaseFieldNames;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getNotSendByMailingResponse {
  /* NotSendContacts */
  public $notSendContact;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getNotSentByMailing {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $mailingId;
  /* string */
  public $databaseFieldNames;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getNotSentByMailingResponse {
  /* NotSentContacts */
  public $notSentContacts;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getOpensByMailing {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $mailingId;
  /* TimeRange */
  public $timeRange;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getOpensByMailingResponse {
  /* Open */
  public $openInfo;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getSummaryReport {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $mailingId;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getSummaryReportResponse {
  /* MailingSummaryReport */
  public $summaryReport;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getWorkspaces {
  /* AuthInfo */
  public $authInfo;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class getWorkspacesResponse {
  /* Workspace */
  public $workspaceInfo;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class importContacts {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* string */
  public $contactGroupName;
  /* string */
  public $importMode;
  /* string */
  public $reportReceiver;
  /* Attachment */
  public $importFile;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class importContactsResponse {
  /* string */
  public $importId;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class invalidAuthInfoFault {
  /* string */
  public $msg;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class invalidInputFault {
  /* string */
  public $msg;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class previewDirectEmail {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $workspaceName;
  /* string */
  public $directEmailName;
  /* string */
  public $directEmailId;
  /* string */
  public $contactId;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class previewDirectEmailResponse {
  /* DirectEmail */
  public $directEmail;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class previewNewsletter {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $workspaceName;
  /* string */
  public $newsletterName;
  /* string */
  public $newsletterId;
  /* string */
  public $contactId;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class previewNewsletterResponse {
  /* NewsletterPreview */
  public $newsletterPreview;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class removeContactGroupMembership {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* string */
  public $contactId;
  /* string */
  public $groupMembership;
  /* string */
  public $refName;
  /* string */
  public $refIp;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class removeContactGroupMembershipResponse {
  /* string */
  public $result;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class scheduleMailing {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* string */
  public $contactGroupName;
  /* string */
  public $testContactGroupName;
  /* string */
  public $smartGroupName;
  /* string */
  public $workspaceName;
  /* string */
  public $directEmailName;
  /* string */
  public $newsletterName;
  /* boolean */
  public $testMailing;
  /* int */
  public $sampleRate;
  /* int */
  public $mailsPerHour;
  /* dateTime */
  public $embargoDate;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class scheduleMailingResponse {
  /* string */
  public $jobId;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class searchContacts {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* SearchParameter */
  public $searchParameter;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class searchContactsResponse {
  /* ContactInfo */
  public $contactInfo;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class TellAFriend {
  /* string */
  public $email;
  /* KeyValuePair */
  public $profileFields;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class tellAFriendResponse {
  /* ApiResponse */
  public $response;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class tripolisUnknownFault {
  /* string */
  public $msg;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class typeOfLink {
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class upsertArticle {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $workspaceName;
  /* ArticleInfo */
  public $articleInfo;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class upsertArticleResponse {
  /* string */
  public $articleId;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class upsertContact {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* GroupMembershipInfo */
  public $groupMembership;
  /* ContactInfo */
  public $contactInfo;
  /* string */
  public $workspaceName;
  /* string */
  public $emailName;
  /* string */
  public $externalResponseHttp;
  /* string */
  public $refName;
  /* string */
  public $refIp;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class upsertContactResponse {
  /* string */
  public $response;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class upsertContactWithNewsletterResponse {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $databaseName;
  /* GroupMembershipInfo */
  public $groupMembership;
  /* ContactInfo */
  public $contactInfo;
  /* string */
  public $workspaceName;
  /* string */
  public $newsletterName;
  /* string */
  public $externalResponseHttp;
  /* string */
  public $refName;
  /* string */
  public $refIp;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class upsertContactWithNewsletterResponseResponse {
  /* string */
  public $response;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class upsertDirectMail {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $workspaceName;
  /* string */
  public $directEmailTypeName;
  /* DirectEmail */
  public $directEmail;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class upsertDirectMailResponse {
  /* string */
  public $directEmailId;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class upsertImage {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $workspaceName;
  /* ImageInfo */
  public $image;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class upsertImageResponse {
  /* string */
  public $imageId;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class upsertNewsletter {
  /* AuthInfo */
  public $authInfo;
  /* string */
  public $workspaceName;
  /* NewsletterInfo */
  public $newsletterInfo;
}

/**
 * 
 * 
 * @package
 * @copyright
 */
class upsertNewsletterResponse {
  /* string */
  public $newsletterId;
}

?>
