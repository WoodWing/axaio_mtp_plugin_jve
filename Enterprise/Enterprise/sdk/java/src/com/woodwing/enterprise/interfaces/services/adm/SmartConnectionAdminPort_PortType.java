/**
 * SmartConnectionAdminPort_PortType.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public interface SmartConnectionAdminPort_PortType extends java.rmi.Remote {
    public com.woodwing.enterprise.interfaces.services.adm.LogOnResponse logOn(com.woodwing.enterprise.interfaces.services.adm.LogOnRequest parameters) throws java.rmi.RemoteException;
    public void logOff(com.woodwing.enterprise.interfaces.services.adm.LogOffRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.CreateUsersResponse createUsers(com.woodwing.enterprise.interfaces.services.adm.CreateUsersRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.GetUsersResponse getUsers(com.woodwing.enterprise.interfaces.services.adm.GetUsersRequest parameter) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.ModifyUsersResponse modifyUsers(com.woodwing.enterprise.interfaces.services.adm.ModifyUsersRequest paramaters) throws java.rmi.RemoteException;
    public java.lang.Object deleteUsers(com.woodwing.enterprise.interfaces.services.adm.DeleteUsersRequest paramaters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.CreateUserGroupsResponse createUserGroups(com.woodwing.enterprise.interfaces.services.adm.CreateUserGroupsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.GetUserGroupsResponse getUserGroups(com.woodwing.enterprise.interfaces.services.adm.GetUserGroupsRequest parameter) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.ModifyUserGroupsResponse modifyUserGroups(com.woodwing.enterprise.interfaces.services.adm.ModifyUserGroupsRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object deleteUserGroups(com.woodwing.enterprise.interfaces.services.adm.DeleteUserGroupsRequest parameters) throws java.rmi.RemoteException;
    public void addUsersToGroup(com.woodwing.enterprise.interfaces.services.adm.AddUsersToGroupRequest parameters) throws java.rmi.RemoteException;
    public void removeUsersFromGroup(com.woodwing.enterprise.interfaces.services.adm.RemoveUsersFromGroupRequest parameters) throws java.rmi.RemoteException;
    public void addGroupsToUser(com.woodwing.enterprise.interfaces.services.adm.AddGroupsToUserRequest parameters) throws java.rmi.RemoteException;
    public void removeGroupsFromUser(com.woodwing.enterprise.interfaces.services.adm.RemoveGroupsFromUserRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.CreatePublicationsResponse createPublications(com.woodwing.enterprise.interfaces.services.adm.CreatePublicationsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.GetPublicationsResponse getPublications(com.woodwing.enterprise.interfaces.services.adm.GetPublicationsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.ModifyPublicationsResponse modifyPublications(com.woodwing.enterprise.interfaces.services.adm.ModifyPublicationsRequest parameters) throws java.rmi.RemoteException;
    public void deletePublications(com.woodwing.enterprise.interfaces.services.adm.DeletePublicationsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.CreatePubChannelsResponse createPubChannels(com.woodwing.enterprise.interfaces.services.adm.CreatePubChannelsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.GetPubChannelsResponse getPubChannels(com.woodwing.enterprise.interfaces.services.adm.GetPubChannelsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.ModifyPubChannelsResponse modifyPubChannels(com.woodwing.enterprise.interfaces.services.adm.ModifyPubChannelsRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object deletePubChannels(com.woodwing.enterprise.interfaces.services.adm.DeletePubChannelsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.CreateIssuesResponse createIssues(com.woodwing.enterprise.interfaces.services.adm.CreateIssuesRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.GetIssuesResponse getIssues(com.woodwing.enterprise.interfaces.services.adm.GetIssuesRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.ModifyIssuesResponse modifyIssues(com.woodwing.enterprise.interfaces.services.adm.ModifyIssuesRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object deleteIssues(com.woodwing.enterprise.interfaces.services.adm.DeleteIssuesRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.CopyIssuesResponse copyIssues(com.woodwing.enterprise.interfaces.services.adm.CopyIssuesRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.CreateEditionsResponse createEditions(com.woodwing.enterprise.interfaces.services.adm.CreateEditionsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.GetEditionsResponse getEditions(com.woodwing.enterprise.interfaces.services.adm.GetEditionsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.ModifyEditionsResponse modifyEditions(com.woodwing.enterprise.interfaces.services.adm.ModifyEditionsRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object deleteEditions(com.woodwing.enterprise.interfaces.services.adm.DeleteEditionsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.CreateSectionsResponse createSections(com.woodwing.enterprise.interfaces.services.adm.CreateSectionsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.GetSectionsResponse getSections(com.woodwing.enterprise.interfaces.services.adm.GetSectionsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.ModifySectionsResponse modifySections(com.woodwing.enterprise.interfaces.services.adm.ModifySectionsRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object deleteSections(com.woodwing.enterprise.interfaces.services.adm.DeleteSectionsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.CreateStatusesResponse createStatuses(com.woodwing.enterprise.interfaces.services.adm.CreateStatusesRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.GetStatusesResponse getStatuses(com.woodwing.enterprise.interfaces.services.adm.GetStatusesRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.ModifyStatusesResponse modifyStatuses(com.woodwing.enterprise.interfaces.services.adm.ModifyStatusesRequest parameters) throws java.rmi.RemoteException;
    public void deleteStatuses(com.woodwing.enterprise.interfaces.services.adm.DeleteStatusesRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.CreateAccessProfilesResponse createAccessProfiles(com.woodwing.enterprise.interfaces.services.adm.CreateAccessProfilesRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.GetAccessProfilesResponse getAccessProfiles(com.woodwing.enterprise.interfaces.services.adm.GetAccessProfilesRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.ModifyAccessProfilesResponse modifyAccessProfiles(com.woodwing.enterprise.interfaces.services.adm.ModifyAccessProfilesRequest parameters) throws java.rmi.RemoteException;
    public void deleteAccessProfiles(com.woodwing.enterprise.interfaces.services.adm.DeleteAccessProfilesRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.CreateWorkflowUserGroupAuthorizationsResponse createWorkflowUserGroupAuthorizations(com.woodwing.enterprise.interfaces.services.adm.CreateWorkflowUserGroupAuthorizationsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.GetWorkflowUserGroupAuthorizationsResponse getWorkflowUserGroupAuthorizations(com.woodwing.enterprise.interfaces.services.adm.GetWorkflowUserGroupAuthorizationsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.ModifyWorkflowUserGroupAuthorizationsResponse modifyWorkflowUserGroupAuthorizations(com.woodwing.enterprise.interfaces.services.adm.ModifyWorkflowUserGroupAuthorizationsRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object deleteWorkflowUserGroupAuthorizations(com.woodwing.enterprise.interfaces.services.adm.DeleteWorkflowUserGroupAuthorizationsRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object createPublicationAdminAuthorizations(com.woodwing.enterprise.interfaces.services.adm.CreatePublicationAdminAuthorizationsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.GetPublicationAdminAuthorizationsResponse getPublicationAdminAuthorizations(com.woodwing.enterprise.interfaces.services.adm.GetPublicationAdminAuthorizationsRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object deletePublicationAdminAuthorizations(com.woodwing.enterprise.interfaces.services.adm.DeletePublicationAdminAuthorizationsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.CreateRoutingsResponse createRoutings(com.woodwing.enterprise.interfaces.services.adm.CreateRoutingsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.GetRoutingsResponse getRoutings(com.woodwing.enterprise.interfaces.services.adm.GetRoutingsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.ModifyRoutingsResponse modifyRoutings(com.woodwing.enterprise.interfaces.services.adm.ModifyRoutingsRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object deleteRoutings(com.woodwing.enterprise.interfaces.services.adm.DeleteRoutingsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.AddTemplateObjectsResponse addTemplateObjects(com.woodwing.enterprise.interfaces.services.adm.AddTemplateObjectsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.GetTemplateObjectsResponse getTemplateObjects(com.woodwing.enterprise.interfaces.services.adm.GetTemplateObjectsRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object removeTemplateObjects(com.woodwing.enterprise.interfaces.services.adm.RemoveTemplateObjectsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.CreateAutocompleteTermEntitiesResponse createAutocompleteTermEntities(com.woodwing.enterprise.interfaces.services.adm.CreateAutocompleteTermEntitiesRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermEntitiesResponse getAutocompleteTermEntities(com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermEntitiesRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.ModifyAutocompleteTermEntitiesResponse modifyAutocompleteTermEntities(com.woodwing.enterprise.interfaces.services.adm.ModifyAutocompleteTermEntitiesRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object deleteAutocompleteTermEntities(com.woodwing.enterprise.interfaces.services.adm.DeleteAutocompleteTermEntitiesRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object createAutocompleteTerms(com.woodwing.enterprise.interfaces.services.adm.CreateAutocompleteTermsRequest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermsResponse getAutocompleteTerms(com.woodwing.enterprise.interfaces.services.adm.GetAutocompleteTermsRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object modifyAutocompleteTerms(com.woodwing.enterprise.interfaces.services.adm.ModifyAutocompleteTermsRequest parameters) throws java.rmi.RemoteException;
    public java.lang.Object deleteAutocompleteTerms(com.woodwing.enterprise.interfaces.services.adm.DeleteAutocompleteTermsRequest parameters) throws java.rmi.RemoteException;
}
