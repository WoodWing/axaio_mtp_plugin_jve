/**
 * SmartConnectionPort.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public interface SmartConnectionPort extends java.rmi.Remote {
    public com.woodwing.enterprise.interfaces.services.wfl.GetServersResponse getServers(java.lang.Object parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.LogOnResponse logOn(com.woodwing.enterprise.interfaces.services.wfl.LogOn parameters) throws java.rmi.RemoteException;
    public void logOff(com.woodwing.enterprise.interfaces.services.wfl.LogOff parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.GetUserSettingsResponse getUserSettings(com.woodwing.enterprise.interfaces.services.wfl.GetUserSettings parameters) throws java.rmi.RemoteException;
    public void saveUserSettings(com.woodwing.enterprise.interfaces.services.wfl.SaveUserSettings parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.GetStatesResponse getStates(com.woodwing.enterprise.interfaces.services.wfl.GetStates parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.CreateObjectsResponse createObjects(com.woodwing.enterprise.interfaces.services.wfl.CreateObjects parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.InstantiateTemplateResponse instantiateTemplate(com.woodwing.enterprise.interfaces.services.wfl.InstantiateTemplate parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.GetObjectsResponse getObjects(com.woodwing.enterprise.interfaces.services.wfl.GetObjects parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.QueryObjectsResponse queryObjects(com.woodwing.enterprise.interfaces.services.wfl.QueryObjects parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.SaveObjectsResponse saveObjects(com.woodwing.enterprise.interfaces.services.wfl.SaveObjects parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.LockObjectsResponse lockObjects(com.woodwing.enterprise.interfaces.services.wfl.LockObjects parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.UnlockObjectsResponse unlockObjects(com.woodwing.enterprise.interfaces.services.wfl.UnlockObjects parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectsResponse deleteObjects(com.woodwing.enterprise.interfaces.services.wfl.DeleteObjects parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.RestoreObjectsResponse restoreObjects(com.woodwing.enterprise.interfaces.services.wfl.RestoreObjects parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.CreateObjectRelationsResponse createObjectRelations(com.woodwing.enterprise.interfaces.services.wfl.CreateObjectRelations parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectRelationsResponse updateObjectRelations(com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectRelations parameters) throws java.rmi.RemoteException;
    public void deleteObjectRelations(com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectRelations parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.GetObjectRelationsResponse getObjectRelations(com.woodwing.enterprise.interfaces.services.wfl.GetObjectRelations parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.CreateObjectTargetsResponse createObjectTargets(com.woodwing.enterprise.interfaces.services.wfl.CreateObjectTargets parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectTargetsResponse updateObjectTargets(com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectTargets parameters) throws java.rmi.RemoteException;
    public void deleteObjectTargets(com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectTargets parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.GetVersionResponse getVersion(com.woodwing.enterprise.interfaces.services.wfl.GetVersion parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.ListVersionsResponse listVersions(com.woodwing.enterprise.interfaces.services.wfl.ListVersions parameters) throws java.rmi.RemoteException;
    public void restoreVersion(com.woodwing.enterprise.interfaces.services.wfl.RestoreVersion parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.CreateArticleWorkspaceResponse createArticleWorkspace(com.woodwing.enterprise.interfaces.services.wfl.CreateArticleWorkspace parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.ListArticleWorkspacesResponse listArticleWorkspaces(com.woodwing.enterprise.interfaces.services.wfl.ListArticleWorkspaces parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.GetArticleFromWorkspaceResponse getArticleFromWorkspace(com.woodwing.enterprise.interfaces.services.wfl.GetArticleFromWorkspace parameters) throws java.rmi.RemoteException;
    public void saveArticleInWorkspace(com.woodwing.enterprise.interfaces.services.wfl.SaveArticleInWorkspace parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.PreviewArticleAtWorkspaceResponse previewArticleAtWorkspace(com.woodwing.enterprise.interfaces.services.wfl.PreviewArticleAtWorkspace parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.PreviewArticlesAtWorkspaceResponse previewArticlesAtWorkspace(com.woodwing.enterprise.interfaces.services.wfl.PreviewArticlesAtWorkspace parameters) throws java.rmi.RemoteException;
    public void deleteArticleWorkspace(com.woodwing.enterprise.interfaces.services.wfl.DeleteArticleWorkspace parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingResponse checkSpelling(com.woodwing.enterprise.interfaces.services.wfl.CheckSpelling parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.GetSuggestionsResponse getSuggestions(com.woodwing.enterprise.interfaces.services.wfl.GetSuggestions parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingAndSuggestResponse checkSpellingAndSuggest(com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingAndSuggest parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.NamedQueryResponse namedQuery(com.woodwing.enterprise.interfaces.services.wfl.NamedQueryType0 parameters) throws java.rmi.RemoteException;
    public void changePassword(com.woodwing.enterprise.interfaces.services.wfl.ChangePassword parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.SendMessagesResponse sendMessages(com.woodwing.enterprise.interfaces.services.wfl.SendMessages parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.CreateObjectOperationsResponse createObjectOperations(com.woodwing.enterprise.interfaces.services.wfl.CreateObjectOperations parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.CopyObjectResponse copyObject(com.woodwing.enterprise.interfaces.services.wfl.CopyObject parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.SetObjectPropertiesResponse setObjectProperties(com.woodwing.enterprise.interfaces.services.wfl.SetObjectProperties parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.MultiSetObjectPropertiesResponse multiSetObjectProperties(com.woodwing.enterprise.interfaces.services.wfl.MultiSetObjectProperties parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.SendToResponse sendTo(com.woodwing.enterprise.interfaces.services.wfl.SendTo parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.SendToNextResponse sendToNext(com.woodwing.enterprise.interfaces.services.wfl.SendToNext parameters) throws java.rmi.RemoteException;
    public void changeOnlineStatus(com.woodwing.enterprise.interfaces.services.wfl.ChangeOnlineStatus parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.GetDialogResponse getDialog(com.woodwing.enterprise.interfaces.services.wfl.GetDialog parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.GetDialog2Response getDialog2(com.woodwing.enterprise.interfaces.services.wfl.GetDialog2 parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.GetPagesResponse getPages(com.woodwing.enterprise.interfaces.services.wfl.GetPages parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.GetPagesInfoResponse getPagesInfo(com.woodwing.enterprise.interfaces.services.wfl.GetPagesInfo parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.AutocompleteResponse autocomplete(com.woodwing.enterprise.interfaces.services.wfl.Autocomplete parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.SuggestionsResponse suggestions(com.woodwing.enterprise.interfaces.services.wfl.Suggestions parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.CreateObjectLabelsResponse createObjectLabels(com.woodwing.enterprise.interfaces.services.wfl.CreateObjectLabels parameters) throws java.rmi.RemoteException;
    public com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectLabelsResponse updateObjectLabels(com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectLabels parameters) throws java.rmi.RemoteException;
    public void deleteObjectLabels(com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectLabels parameters) throws java.rmi.RemoteException;
    public void addObjectLabels(com.woodwing.enterprise.interfaces.services.wfl.AddObjectLabels parameters) throws java.rmi.RemoteException;
    public void removeObjectLabels(com.woodwing.enterprise.interfaces.services.wfl.RemoveObjectLabels parameters) throws java.rmi.RemoteException;
}
