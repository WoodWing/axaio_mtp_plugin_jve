/**
 * LogOnResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class LogOnResponse  implements java.io.Serializable {
    private java.lang.String ticket;

    private com.woodwing.enterprise.interfaces.services.wfl.PublicationInfo[] publications;

    private com.woodwing.enterprise.interfaces.services.wfl.NamedQuery[] namedQueries;

    private com.woodwing.enterprise.interfaces.services.wfl.Feature[] featureSet;

    private java.lang.String[] limitationSet;

    private com.woodwing.enterprise.interfaces.services.wfl.ServerInfo serverInfo;

    private com.woodwing.enterprise.interfaces.services.wfl.Setting[] settings;

    private com.woodwing.enterprise.interfaces.services.wfl.User[] users;

    private com.woodwing.enterprise.interfaces.services.wfl.UserGroup[] userGroups;

    private com.woodwing.enterprise.interfaces.services.wfl.UserGroup[] membership;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectTypeProperty[] objectTypeProperties;

    private com.woodwing.enterprise.interfaces.services.wfl.ActionProperty[] actionProperties;

    private com.woodwing.enterprise.interfaces.services.wfl.Term[] terms;

    private com.woodwing.enterprise.interfaces.services.wfl.FeatureProfile[] featureProfiles;

    private com.woodwing.enterprise.interfaces.services.wfl.Message[] messages;

    private java.lang.String trackChangesColor;

    private com.woodwing.enterprise.interfaces.services.wfl.Dictionary[] dictionaries;

    private com.woodwing.enterprise.interfaces.services.wfl.MessageList messageList;

    private com.woodwing.enterprise.interfaces.services.wfl.User currentUser;

    private com.woodwing.enterprise.interfaces.services.wfl.MessageQueueConnection[] messageQueueConnections;

    private java.lang.String messageQueue;

    public LogOnResponse() {
    }

    public LogOnResponse(
           java.lang.String ticket,
           com.woodwing.enterprise.interfaces.services.wfl.PublicationInfo[] publications,
           com.woodwing.enterprise.interfaces.services.wfl.NamedQuery[] namedQueries,
           com.woodwing.enterprise.interfaces.services.wfl.Feature[] featureSet,
           java.lang.String[] limitationSet,
           com.woodwing.enterprise.interfaces.services.wfl.ServerInfo serverInfo,
           com.woodwing.enterprise.interfaces.services.wfl.Setting[] settings,
           com.woodwing.enterprise.interfaces.services.wfl.User[] users,
           com.woodwing.enterprise.interfaces.services.wfl.UserGroup[] userGroups,
           com.woodwing.enterprise.interfaces.services.wfl.UserGroup[] membership,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectTypeProperty[] objectTypeProperties,
           com.woodwing.enterprise.interfaces.services.wfl.ActionProperty[] actionProperties,
           com.woodwing.enterprise.interfaces.services.wfl.Term[] terms,
           com.woodwing.enterprise.interfaces.services.wfl.FeatureProfile[] featureProfiles,
           com.woodwing.enterprise.interfaces.services.wfl.Message[] messages,
           java.lang.String trackChangesColor,
           com.woodwing.enterprise.interfaces.services.wfl.Dictionary[] dictionaries,
           com.woodwing.enterprise.interfaces.services.wfl.MessageList messageList,
           com.woodwing.enterprise.interfaces.services.wfl.User currentUser,
           com.woodwing.enterprise.interfaces.services.wfl.MessageQueueConnection[] messageQueueConnections,
           java.lang.String messageQueue) {
           this.ticket = ticket;
           this.publications = publications;
           this.namedQueries = namedQueries;
           this.featureSet = featureSet;
           this.limitationSet = limitationSet;
           this.serverInfo = serverInfo;
           this.settings = settings;
           this.users = users;
           this.userGroups = userGroups;
           this.membership = membership;
           this.objectTypeProperties = objectTypeProperties;
           this.actionProperties = actionProperties;
           this.terms = terms;
           this.featureProfiles = featureProfiles;
           this.messages = messages;
           this.trackChangesColor = trackChangesColor;
           this.dictionaries = dictionaries;
           this.messageList = messageList;
           this.currentUser = currentUser;
           this.messageQueueConnections = messageQueueConnections;
           this.messageQueue = messageQueue;
    }


    /**
     * Gets the ticket value for this LogOnResponse.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this LogOnResponse.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the publications value for this LogOnResponse.
     * 
     * @return publications
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PublicationInfo[] getPublications() {
        return publications;
    }


    /**
     * Sets the publications value for this LogOnResponse.
     * 
     * @param publications
     */
    public void setPublications(com.woodwing.enterprise.interfaces.services.wfl.PublicationInfo[] publications) {
        this.publications = publications;
    }


    /**
     * Gets the namedQueries value for this LogOnResponse.
     * 
     * @return namedQueries
     */
    public com.woodwing.enterprise.interfaces.services.wfl.NamedQuery[] getNamedQueries() {
        return namedQueries;
    }


    /**
     * Sets the namedQueries value for this LogOnResponse.
     * 
     * @param namedQueries
     */
    public void setNamedQueries(com.woodwing.enterprise.interfaces.services.wfl.NamedQuery[] namedQueries) {
        this.namedQueries = namedQueries;
    }


    /**
     * Gets the featureSet value for this LogOnResponse.
     * 
     * @return featureSet
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Feature[] getFeatureSet() {
        return featureSet;
    }


    /**
     * Sets the featureSet value for this LogOnResponse.
     * 
     * @param featureSet
     */
    public void setFeatureSet(com.woodwing.enterprise.interfaces.services.wfl.Feature[] featureSet) {
        this.featureSet = featureSet;
    }


    /**
     * Gets the limitationSet value for this LogOnResponse.
     * 
     * @return limitationSet
     */
    public java.lang.String[] getLimitationSet() {
        return limitationSet;
    }


    /**
     * Sets the limitationSet value for this LogOnResponse.
     * 
     * @param limitationSet
     */
    public void setLimitationSet(java.lang.String[] limitationSet) {
        this.limitationSet = limitationSet;
    }


    /**
     * Gets the serverInfo value for this LogOnResponse.
     * 
     * @return serverInfo
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ServerInfo getServerInfo() {
        return serverInfo;
    }


    /**
     * Sets the serverInfo value for this LogOnResponse.
     * 
     * @param serverInfo
     */
    public void setServerInfo(com.woodwing.enterprise.interfaces.services.wfl.ServerInfo serverInfo) {
        this.serverInfo = serverInfo;
    }


    /**
     * Gets the settings value for this LogOnResponse.
     * 
     * @return settings
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Setting[] getSettings() {
        return settings;
    }


    /**
     * Sets the settings value for this LogOnResponse.
     * 
     * @param settings
     */
    public void setSettings(com.woodwing.enterprise.interfaces.services.wfl.Setting[] settings) {
        this.settings = settings;
    }


    /**
     * Gets the users value for this LogOnResponse.
     * 
     * @return users
     */
    public com.woodwing.enterprise.interfaces.services.wfl.User[] getUsers() {
        return users;
    }


    /**
     * Sets the users value for this LogOnResponse.
     * 
     * @param users
     */
    public void setUsers(com.woodwing.enterprise.interfaces.services.wfl.User[] users) {
        this.users = users;
    }


    /**
     * Gets the userGroups value for this LogOnResponse.
     * 
     * @return userGroups
     */
    public com.woodwing.enterprise.interfaces.services.wfl.UserGroup[] getUserGroups() {
        return userGroups;
    }


    /**
     * Sets the userGroups value for this LogOnResponse.
     * 
     * @param userGroups
     */
    public void setUserGroups(com.woodwing.enterprise.interfaces.services.wfl.UserGroup[] userGroups) {
        this.userGroups = userGroups;
    }


    /**
     * Gets the membership value for this LogOnResponse.
     * 
     * @return membership
     */
    public com.woodwing.enterprise.interfaces.services.wfl.UserGroup[] getMembership() {
        return membership;
    }


    /**
     * Sets the membership value for this LogOnResponse.
     * 
     * @param membership
     */
    public void setMembership(com.woodwing.enterprise.interfaces.services.wfl.UserGroup[] membership) {
        this.membership = membership;
    }


    /**
     * Gets the objectTypeProperties value for this LogOnResponse.
     * 
     * @return objectTypeProperties
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectTypeProperty[] getObjectTypeProperties() {
        return objectTypeProperties;
    }


    /**
     * Sets the objectTypeProperties value for this LogOnResponse.
     * 
     * @param objectTypeProperties
     */
    public void setObjectTypeProperties(com.woodwing.enterprise.interfaces.services.wfl.ObjectTypeProperty[] objectTypeProperties) {
        this.objectTypeProperties = objectTypeProperties;
    }


    /**
     * Gets the actionProperties value for this LogOnResponse.
     * 
     * @return actionProperties
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ActionProperty[] getActionProperties() {
        return actionProperties;
    }


    /**
     * Sets the actionProperties value for this LogOnResponse.
     * 
     * @param actionProperties
     */
    public void setActionProperties(com.woodwing.enterprise.interfaces.services.wfl.ActionProperty[] actionProperties) {
        this.actionProperties = actionProperties;
    }


    /**
     * Gets the terms value for this LogOnResponse.
     * 
     * @return terms
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Term[] getTerms() {
        return terms;
    }


    /**
     * Sets the terms value for this LogOnResponse.
     * 
     * @param terms
     */
    public void setTerms(com.woodwing.enterprise.interfaces.services.wfl.Term[] terms) {
        this.terms = terms;
    }


    /**
     * Gets the featureProfiles value for this LogOnResponse.
     * 
     * @return featureProfiles
     */
    public com.woodwing.enterprise.interfaces.services.wfl.FeatureProfile[] getFeatureProfiles() {
        return featureProfiles;
    }


    /**
     * Sets the featureProfiles value for this LogOnResponse.
     * 
     * @param featureProfiles
     */
    public void setFeatureProfiles(com.woodwing.enterprise.interfaces.services.wfl.FeatureProfile[] featureProfiles) {
        this.featureProfiles = featureProfiles;
    }


    /**
     * Gets the messages value for this LogOnResponse.
     * 
     * @return messages
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Message[] getMessages() {
        return messages;
    }


    /**
     * Sets the messages value for this LogOnResponse.
     * 
     * @param messages
     */
    public void setMessages(com.woodwing.enterprise.interfaces.services.wfl.Message[] messages) {
        this.messages = messages;
    }


    /**
     * Gets the trackChangesColor value for this LogOnResponse.
     * 
     * @return trackChangesColor
     */
    public java.lang.String getTrackChangesColor() {
        return trackChangesColor;
    }


    /**
     * Sets the trackChangesColor value for this LogOnResponse.
     * 
     * @param trackChangesColor
     */
    public void setTrackChangesColor(java.lang.String trackChangesColor) {
        this.trackChangesColor = trackChangesColor;
    }


    /**
     * Gets the dictionaries value for this LogOnResponse.
     * 
     * @return dictionaries
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Dictionary[] getDictionaries() {
        return dictionaries;
    }


    /**
     * Sets the dictionaries value for this LogOnResponse.
     * 
     * @param dictionaries
     */
    public void setDictionaries(com.woodwing.enterprise.interfaces.services.wfl.Dictionary[] dictionaries) {
        this.dictionaries = dictionaries;
    }


    /**
     * Gets the messageList value for this LogOnResponse.
     * 
     * @return messageList
     */
    public com.woodwing.enterprise.interfaces.services.wfl.MessageList getMessageList() {
        return messageList;
    }


    /**
     * Sets the messageList value for this LogOnResponse.
     * 
     * @param messageList
     */
    public void setMessageList(com.woodwing.enterprise.interfaces.services.wfl.MessageList messageList) {
        this.messageList = messageList;
    }


    /**
     * Gets the currentUser value for this LogOnResponse.
     * 
     * @return currentUser
     */
    public com.woodwing.enterprise.interfaces.services.wfl.User getCurrentUser() {
        return currentUser;
    }


    /**
     * Sets the currentUser value for this LogOnResponse.
     * 
     * @param currentUser
     */
    public void setCurrentUser(com.woodwing.enterprise.interfaces.services.wfl.User currentUser) {
        this.currentUser = currentUser;
    }


    /**
     * Gets the messageQueueConnections value for this LogOnResponse.
     * 
     * @return messageQueueConnections
     */
    public com.woodwing.enterprise.interfaces.services.wfl.MessageQueueConnection[] getMessageQueueConnections() {
        return messageQueueConnections;
    }


    /**
     * Sets the messageQueueConnections value for this LogOnResponse.
     * 
     * @param messageQueueConnections
     */
    public void setMessageQueueConnections(com.woodwing.enterprise.interfaces.services.wfl.MessageQueueConnection[] messageQueueConnections) {
        this.messageQueueConnections = messageQueueConnections;
    }


    /**
     * Gets the messageQueue value for this LogOnResponse.
     * 
     * @return messageQueue
     */
    public java.lang.String getMessageQueue() {
        return messageQueue;
    }


    /**
     * Sets the messageQueue value for this LogOnResponse.
     * 
     * @param messageQueue
     */
    public void setMessageQueue(java.lang.String messageQueue) {
        this.messageQueue = messageQueue;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof LogOnResponse)) return false;
        LogOnResponse other = (LogOnResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ticket==null && other.getTicket()==null) || 
             (this.ticket!=null &&
              this.ticket.equals(other.getTicket()))) &&
            ((this.publications==null && other.getPublications()==null) || 
             (this.publications!=null &&
              java.util.Arrays.equals(this.publications, other.getPublications()))) &&
            ((this.namedQueries==null && other.getNamedQueries()==null) || 
             (this.namedQueries!=null &&
              java.util.Arrays.equals(this.namedQueries, other.getNamedQueries()))) &&
            ((this.featureSet==null && other.getFeatureSet()==null) || 
             (this.featureSet!=null &&
              java.util.Arrays.equals(this.featureSet, other.getFeatureSet()))) &&
            ((this.limitationSet==null && other.getLimitationSet()==null) || 
             (this.limitationSet!=null &&
              java.util.Arrays.equals(this.limitationSet, other.getLimitationSet()))) &&
            ((this.serverInfo==null && other.getServerInfo()==null) || 
             (this.serverInfo!=null &&
              this.serverInfo.equals(other.getServerInfo()))) &&
            ((this.settings==null && other.getSettings()==null) || 
             (this.settings!=null &&
              java.util.Arrays.equals(this.settings, other.getSettings()))) &&
            ((this.users==null && other.getUsers()==null) || 
             (this.users!=null &&
              java.util.Arrays.equals(this.users, other.getUsers()))) &&
            ((this.userGroups==null && other.getUserGroups()==null) || 
             (this.userGroups!=null &&
              java.util.Arrays.equals(this.userGroups, other.getUserGroups()))) &&
            ((this.membership==null && other.getMembership()==null) || 
             (this.membership!=null &&
              java.util.Arrays.equals(this.membership, other.getMembership()))) &&
            ((this.objectTypeProperties==null && other.getObjectTypeProperties()==null) || 
             (this.objectTypeProperties!=null &&
              java.util.Arrays.equals(this.objectTypeProperties, other.getObjectTypeProperties()))) &&
            ((this.actionProperties==null && other.getActionProperties()==null) || 
             (this.actionProperties!=null &&
              java.util.Arrays.equals(this.actionProperties, other.getActionProperties()))) &&
            ((this.terms==null && other.getTerms()==null) || 
             (this.terms!=null &&
              java.util.Arrays.equals(this.terms, other.getTerms()))) &&
            ((this.featureProfiles==null && other.getFeatureProfiles()==null) || 
             (this.featureProfiles!=null &&
              java.util.Arrays.equals(this.featureProfiles, other.getFeatureProfiles()))) &&
            ((this.messages==null && other.getMessages()==null) || 
             (this.messages!=null &&
              java.util.Arrays.equals(this.messages, other.getMessages()))) &&
            ((this.trackChangesColor==null && other.getTrackChangesColor()==null) || 
             (this.trackChangesColor!=null &&
              this.trackChangesColor.equals(other.getTrackChangesColor()))) &&
            ((this.dictionaries==null && other.getDictionaries()==null) || 
             (this.dictionaries!=null &&
              java.util.Arrays.equals(this.dictionaries, other.getDictionaries()))) &&
            ((this.messageList==null && other.getMessageList()==null) || 
             (this.messageList!=null &&
              this.messageList.equals(other.getMessageList()))) &&
            ((this.currentUser==null && other.getCurrentUser()==null) || 
             (this.currentUser!=null &&
              this.currentUser.equals(other.getCurrentUser()))) &&
            ((this.messageQueueConnections==null && other.getMessageQueueConnections()==null) || 
             (this.messageQueueConnections!=null &&
              java.util.Arrays.equals(this.messageQueueConnections, other.getMessageQueueConnections()))) &&
            ((this.messageQueue==null && other.getMessageQueue()==null) || 
             (this.messageQueue!=null &&
              this.messageQueue.equals(other.getMessageQueue())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getTicket() != null) {
            _hashCode += getTicket().hashCode();
        }
        if (getPublications() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPublications());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPublications(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getNamedQueries() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getNamedQueries());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getNamedQueries(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getFeatureSet() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getFeatureSet());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getFeatureSet(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getLimitationSet() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getLimitationSet());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getLimitationSet(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getServerInfo() != null) {
            _hashCode += getServerInfo().hashCode();
        }
        if (getSettings() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getSettings());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getSettings(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getUsers() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getUsers());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getUsers(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getUserGroups() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getUserGroups());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getUserGroups(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getMembership() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getMembership());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getMembership(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getObjectTypeProperties() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getObjectTypeProperties());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getObjectTypeProperties(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getActionProperties() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getActionProperties());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getActionProperties(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getTerms() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getTerms());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getTerms(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getFeatureProfiles() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getFeatureProfiles());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getFeatureProfiles(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getMessages() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getMessages());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getMessages(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getTrackChangesColor() != null) {
            _hashCode += getTrackChangesColor().hashCode();
        }
        if (getDictionaries() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getDictionaries());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getDictionaries(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getMessageList() != null) {
            _hashCode += getMessageList().hashCode();
        }
        if (getCurrentUser() != null) {
            _hashCode += getCurrentUser().hashCode();
        }
        if (getMessageQueueConnections() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getMessageQueueConnections());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getMessageQueueConnections(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getMessageQueue() != null) {
            _hashCode += getMessageQueue().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(LogOnResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">LogOnResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publications");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Publications"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PublicationInfo"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "PublicationInfo"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("namedQueries");
        elemField.setXmlName(new javax.xml.namespace.QName("", "NamedQueries"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "NamedQuery"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "NamedQuery"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("featureSet");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FeatureSet"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Feature"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Feature"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("limitationSet");
        elemField.setXmlName(new javax.xml.namespace.QName("", "LimitationSet"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("serverInfo");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ServerInfo"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ServerInfo"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("settings");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Settings"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Setting"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Setting"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("users");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Users"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "User"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "User"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("userGroups");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UserGroups"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "UserGroup"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "UserGroup"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("membership");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Membership"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "UserGroup"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "UserGroup"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objectTypeProperties");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ObjectTypeProperties"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectTypeProperty"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ObjectTypeProperty"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("actionProperties");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ActionProperties"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ActionProperty"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ActionProperty"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("terms");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Terms"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Term"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Term"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("featureProfiles");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FeatureProfiles"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "FeatureProfile"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "FeatureProfile"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messages");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Messages"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Message"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Message"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("trackChangesColor");
        elemField.setXmlName(new javax.xml.namespace.QName("", "TrackChangesColor"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("dictionaries");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Dictionaries"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Dictionary"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Dictionary"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messageList");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MessageList"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MessageList"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("currentUser");
        elemField.setXmlName(new javax.xml.namespace.QName("", "CurrentUser"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "User"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messageQueueConnections");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MessageQueueConnections"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MessageQueueConnection"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "MessageQueueConnection"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messageQueue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MessageQueue"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
