/**
 * PublicationInfo.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class PublicationInfo  implements java.io.Serializable {
    private java.lang.String id;

    private java.lang.String name;

    private com.woodwing.enterprise.interfaces.services.wfl.IssueInfo[] issues;

    private com.woodwing.enterprise.interfaces.services.wfl.State[] states;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectTypeProperty[] objectTypeProperties;

    private com.woodwing.enterprise.interfaces.services.wfl.ActionProperty[] actionProperties;

    private com.woodwing.enterprise.interfaces.services.wfl.Edition[] editions;

    private com.woodwing.enterprise.interfaces.services.wfl.FeatureAccess[] featureAccessList;

    private java.lang.String currentIssue;

    private com.woodwing.enterprise.interfaces.services.wfl.PubChannelInfo[] pubChannels;

    private com.woodwing.enterprise.interfaces.services.wfl.CategoryInfo[] categories;

    private com.woodwing.enterprise.interfaces.services.wfl.Dictionary[] dictionaries;

    private boolean reversedRead;

    public PublicationInfo() {
    }

    public PublicationInfo(
           java.lang.String id,
           java.lang.String name,
           com.woodwing.enterprise.interfaces.services.wfl.IssueInfo[] issues,
           com.woodwing.enterprise.interfaces.services.wfl.State[] states,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectTypeProperty[] objectTypeProperties,
           com.woodwing.enterprise.interfaces.services.wfl.ActionProperty[] actionProperties,
           com.woodwing.enterprise.interfaces.services.wfl.Edition[] editions,
           com.woodwing.enterprise.interfaces.services.wfl.FeatureAccess[] featureAccessList,
           java.lang.String currentIssue,
           com.woodwing.enterprise.interfaces.services.wfl.PubChannelInfo[] pubChannels,
           com.woodwing.enterprise.interfaces.services.wfl.CategoryInfo[] categories,
           com.woodwing.enterprise.interfaces.services.wfl.Dictionary[] dictionaries,
           boolean reversedRead) {
           this.id = id;
           this.name = name;
           this.issues = issues;
           this.states = states;
           this.objectTypeProperties = objectTypeProperties;
           this.actionProperties = actionProperties;
           this.editions = editions;
           this.featureAccessList = featureAccessList;
           this.currentIssue = currentIssue;
           this.pubChannels = pubChannels;
           this.categories = categories;
           this.dictionaries = dictionaries;
           this.reversedRead = reversedRead;
    }


    /**
     * Gets the id value for this PublicationInfo.
     * 
     * @return id
     */
    public java.lang.String getId() {
        return id;
    }


    /**
     * Sets the id value for this PublicationInfo.
     * 
     * @param id
     */
    public void setId(java.lang.String id) {
        this.id = id;
    }


    /**
     * Gets the name value for this PublicationInfo.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this PublicationInfo.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the issues value for this PublicationInfo.
     * 
     * @return issues
     */
    public com.woodwing.enterprise.interfaces.services.wfl.IssueInfo[] getIssues() {
        return issues;
    }


    /**
     * Sets the issues value for this PublicationInfo.
     * 
     * @param issues
     */
    public void setIssues(com.woodwing.enterprise.interfaces.services.wfl.IssueInfo[] issues) {
        this.issues = issues;
    }


    /**
     * Gets the states value for this PublicationInfo.
     * 
     * @return states
     */
    public com.woodwing.enterprise.interfaces.services.wfl.State[] getStates() {
        return states;
    }


    /**
     * Sets the states value for this PublicationInfo.
     * 
     * @param states
     */
    public void setStates(com.woodwing.enterprise.interfaces.services.wfl.State[] states) {
        this.states = states;
    }


    /**
     * Gets the objectTypeProperties value for this PublicationInfo.
     * 
     * @return objectTypeProperties
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectTypeProperty[] getObjectTypeProperties() {
        return objectTypeProperties;
    }


    /**
     * Sets the objectTypeProperties value for this PublicationInfo.
     * 
     * @param objectTypeProperties
     */
    public void setObjectTypeProperties(com.woodwing.enterprise.interfaces.services.wfl.ObjectTypeProperty[] objectTypeProperties) {
        this.objectTypeProperties = objectTypeProperties;
    }


    /**
     * Gets the actionProperties value for this PublicationInfo.
     * 
     * @return actionProperties
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ActionProperty[] getActionProperties() {
        return actionProperties;
    }


    /**
     * Sets the actionProperties value for this PublicationInfo.
     * 
     * @param actionProperties
     */
    public void setActionProperties(com.woodwing.enterprise.interfaces.services.wfl.ActionProperty[] actionProperties) {
        this.actionProperties = actionProperties;
    }


    /**
     * Gets the editions value for this PublicationInfo.
     * 
     * @return editions
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Edition[] getEditions() {
        return editions;
    }


    /**
     * Sets the editions value for this PublicationInfo.
     * 
     * @param editions
     */
    public void setEditions(com.woodwing.enterprise.interfaces.services.wfl.Edition[] editions) {
        this.editions = editions;
    }


    /**
     * Gets the featureAccessList value for this PublicationInfo.
     * 
     * @return featureAccessList
     */
    public com.woodwing.enterprise.interfaces.services.wfl.FeatureAccess[] getFeatureAccessList() {
        return featureAccessList;
    }


    /**
     * Sets the featureAccessList value for this PublicationInfo.
     * 
     * @param featureAccessList
     */
    public void setFeatureAccessList(com.woodwing.enterprise.interfaces.services.wfl.FeatureAccess[] featureAccessList) {
        this.featureAccessList = featureAccessList;
    }


    /**
     * Gets the currentIssue value for this PublicationInfo.
     * 
     * @return currentIssue
     */
    public java.lang.String getCurrentIssue() {
        return currentIssue;
    }


    /**
     * Sets the currentIssue value for this PublicationInfo.
     * 
     * @param currentIssue
     */
    public void setCurrentIssue(java.lang.String currentIssue) {
        this.currentIssue = currentIssue;
    }


    /**
     * Gets the pubChannels value for this PublicationInfo.
     * 
     * @return pubChannels
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PubChannelInfo[] getPubChannels() {
        return pubChannels;
    }


    /**
     * Sets the pubChannels value for this PublicationInfo.
     * 
     * @param pubChannels
     */
    public void setPubChannels(com.woodwing.enterprise.interfaces.services.wfl.PubChannelInfo[] pubChannels) {
        this.pubChannels = pubChannels;
    }


    /**
     * Gets the categories value for this PublicationInfo.
     * 
     * @return categories
     */
    public com.woodwing.enterprise.interfaces.services.wfl.CategoryInfo[] getCategories() {
        return categories;
    }


    /**
     * Sets the categories value for this PublicationInfo.
     * 
     * @param categories
     */
    public void setCategories(com.woodwing.enterprise.interfaces.services.wfl.CategoryInfo[] categories) {
        this.categories = categories;
    }


    /**
     * Gets the dictionaries value for this PublicationInfo.
     * 
     * @return dictionaries
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Dictionary[] getDictionaries() {
        return dictionaries;
    }


    /**
     * Sets the dictionaries value for this PublicationInfo.
     * 
     * @param dictionaries
     */
    public void setDictionaries(com.woodwing.enterprise.interfaces.services.wfl.Dictionary[] dictionaries) {
        this.dictionaries = dictionaries;
    }


    /**
     * Gets the reversedRead value for this PublicationInfo.
     * 
     * @return reversedRead
     */
    public boolean isReversedRead() {
        return reversedRead;
    }


    /**
     * Sets the reversedRead value for this PublicationInfo.
     * 
     * @param reversedRead
     */
    public void setReversedRead(boolean reversedRead) {
        this.reversedRead = reversedRead;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof PublicationInfo)) return false;
        PublicationInfo other = (PublicationInfo) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.id==null && other.getId()==null) || 
             (this.id!=null &&
              this.id.equals(other.getId()))) &&
            ((this.name==null && other.getName()==null) || 
             (this.name!=null &&
              this.name.equals(other.getName()))) &&
            ((this.issues==null && other.getIssues()==null) || 
             (this.issues!=null &&
              java.util.Arrays.equals(this.issues, other.getIssues()))) &&
            ((this.states==null && other.getStates()==null) || 
             (this.states!=null &&
              java.util.Arrays.equals(this.states, other.getStates()))) &&
            ((this.objectTypeProperties==null && other.getObjectTypeProperties()==null) || 
             (this.objectTypeProperties!=null &&
              java.util.Arrays.equals(this.objectTypeProperties, other.getObjectTypeProperties()))) &&
            ((this.actionProperties==null && other.getActionProperties()==null) || 
             (this.actionProperties!=null &&
              java.util.Arrays.equals(this.actionProperties, other.getActionProperties()))) &&
            ((this.editions==null && other.getEditions()==null) || 
             (this.editions!=null &&
              java.util.Arrays.equals(this.editions, other.getEditions()))) &&
            ((this.featureAccessList==null && other.getFeatureAccessList()==null) || 
             (this.featureAccessList!=null &&
              java.util.Arrays.equals(this.featureAccessList, other.getFeatureAccessList()))) &&
            ((this.currentIssue==null && other.getCurrentIssue()==null) || 
             (this.currentIssue!=null &&
              this.currentIssue.equals(other.getCurrentIssue()))) &&
            ((this.pubChannels==null && other.getPubChannels()==null) || 
             (this.pubChannels!=null &&
              java.util.Arrays.equals(this.pubChannels, other.getPubChannels()))) &&
            ((this.categories==null && other.getCategories()==null) || 
             (this.categories!=null &&
              java.util.Arrays.equals(this.categories, other.getCategories()))) &&
            ((this.dictionaries==null && other.getDictionaries()==null) || 
             (this.dictionaries!=null &&
              java.util.Arrays.equals(this.dictionaries, other.getDictionaries()))) &&
            this.reversedRead == other.isReversedRead();
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
        if (getId() != null) {
            _hashCode += getId().hashCode();
        }
        if (getName() != null) {
            _hashCode += getName().hashCode();
        }
        if (getIssues() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getIssues());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getIssues(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getStates() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getStates());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getStates(), i);
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
        if (getEditions() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getEditions());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getEditions(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getFeatureAccessList() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getFeatureAccessList());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getFeatureAccessList(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getCurrentIssue() != null) {
            _hashCode += getCurrentIssue().hashCode();
        }
        if (getPubChannels() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPubChannels());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPubChannels(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getCategories() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getCategories());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getCategories(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
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
        _hashCode += (isReversedRead() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(PublicationInfo.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PublicationInfo"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("id");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Id"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("issues");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Issues"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "IssueInfo"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IssueInfo"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("states");
        elemField.setXmlName(new javax.xml.namespace.QName("", "States"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "State"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "State"));
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
        elemField.setFieldName("editions");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Editions"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Edition"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Edition"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("featureAccessList");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FeatureAccessList"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "FeatureAccess"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "FeatureAccess"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("currentIssue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "CurrentIssue"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pubChannels");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PubChannels"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PubChannelInfo"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "PubChannelInfo"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("categories");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Categories"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "CategoryInfo"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "CategoryInfo"));
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
        elemField.setFieldName("reversedRead");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ReversedRead"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
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
