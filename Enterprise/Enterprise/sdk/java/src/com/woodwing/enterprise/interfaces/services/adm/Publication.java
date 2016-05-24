/**
 * Publication.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class Publication  implements java.io.Serializable {
    private java.math.BigInteger id;

    private java.lang.String name;

    private java.lang.String description;

    private org.apache.axis.types.UnsignedInt sortOrder;

    private java.lang.Boolean emailNotify;

    private java.lang.Boolean reversedRead;

    private org.apache.axis.types.UnsignedInt autoPurge;

    private java.math.BigInteger defaultChannelId;

    private com.woodwing.enterprise.interfaces.services.adm.ExtraMetaData[] extraMetaData;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] pubChannels;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] issues;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] editions;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] sections;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] statuses;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] userGroups;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] adminGroups;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] workflows;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] routings;

    private java.lang.Boolean calculateDeadlines;

    public Publication() {
    }

    public Publication(
           java.math.BigInteger id,
           java.lang.String name,
           java.lang.String description,
           org.apache.axis.types.UnsignedInt sortOrder,
           java.lang.Boolean emailNotify,
           java.lang.Boolean reversedRead,
           org.apache.axis.types.UnsignedInt autoPurge,
           java.math.BigInteger defaultChannelId,
           com.woodwing.enterprise.interfaces.services.adm.ExtraMetaData[] extraMetaData,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] pubChannels,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] issues,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] editions,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] sections,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] statuses,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] userGroups,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] adminGroups,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] workflows,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] routings,
           java.lang.Boolean calculateDeadlines) {
           this.id = id;
           this.name = name;
           this.description = description;
           this.sortOrder = sortOrder;
           this.emailNotify = emailNotify;
           this.reversedRead = reversedRead;
           this.autoPurge = autoPurge;
           this.defaultChannelId = defaultChannelId;
           this.extraMetaData = extraMetaData;
           this.pubChannels = pubChannels;
           this.issues = issues;
           this.editions = editions;
           this.sections = sections;
           this.statuses = statuses;
           this.userGroups = userGroups;
           this.adminGroups = adminGroups;
           this.workflows = workflows;
           this.routings = routings;
           this.calculateDeadlines = calculateDeadlines;
    }


    /**
     * Gets the id value for this Publication.
     * 
     * @return id
     */
    public java.math.BigInteger getId() {
        return id;
    }


    /**
     * Sets the id value for this Publication.
     * 
     * @param id
     */
    public void setId(java.math.BigInteger id) {
        this.id = id;
    }


    /**
     * Gets the name value for this Publication.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this Publication.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the description value for this Publication.
     * 
     * @return description
     */
    public java.lang.String getDescription() {
        return description;
    }


    /**
     * Sets the description value for this Publication.
     * 
     * @param description
     */
    public void setDescription(java.lang.String description) {
        this.description = description;
    }


    /**
     * Gets the sortOrder value for this Publication.
     * 
     * @return sortOrder
     */
    public org.apache.axis.types.UnsignedInt getSortOrder() {
        return sortOrder;
    }


    /**
     * Sets the sortOrder value for this Publication.
     * 
     * @param sortOrder
     */
    public void setSortOrder(org.apache.axis.types.UnsignedInt sortOrder) {
        this.sortOrder = sortOrder;
    }


    /**
     * Gets the emailNotify value for this Publication.
     * 
     * @return emailNotify
     */
    public java.lang.Boolean getEmailNotify() {
        return emailNotify;
    }


    /**
     * Sets the emailNotify value for this Publication.
     * 
     * @param emailNotify
     */
    public void setEmailNotify(java.lang.Boolean emailNotify) {
        this.emailNotify = emailNotify;
    }


    /**
     * Gets the reversedRead value for this Publication.
     * 
     * @return reversedRead
     */
    public java.lang.Boolean getReversedRead() {
        return reversedRead;
    }


    /**
     * Sets the reversedRead value for this Publication.
     * 
     * @param reversedRead
     */
    public void setReversedRead(java.lang.Boolean reversedRead) {
        this.reversedRead = reversedRead;
    }


    /**
     * Gets the autoPurge value for this Publication.
     * 
     * @return autoPurge
     */
    public org.apache.axis.types.UnsignedInt getAutoPurge() {
        return autoPurge;
    }


    /**
     * Sets the autoPurge value for this Publication.
     * 
     * @param autoPurge
     */
    public void setAutoPurge(org.apache.axis.types.UnsignedInt autoPurge) {
        this.autoPurge = autoPurge;
    }


    /**
     * Gets the defaultChannelId value for this Publication.
     * 
     * @return defaultChannelId
     */
    public java.math.BigInteger getDefaultChannelId() {
        return defaultChannelId;
    }


    /**
     * Sets the defaultChannelId value for this Publication.
     * 
     * @param defaultChannelId
     */
    public void setDefaultChannelId(java.math.BigInteger defaultChannelId) {
        this.defaultChannelId = defaultChannelId;
    }


    /**
     * Gets the extraMetaData value for this Publication.
     * 
     * @return extraMetaData
     */
    public com.woodwing.enterprise.interfaces.services.adm.ExtraMetaData[] getExtraMetaData() {
        return extraMetaData;
    }


    /**
     * Sets the extraMetaData value for this Publication.
     * 
     * @param extraMetaData
     */
    public void setExtraMetaData(com.woodwing.enterprise.interfaces.services.adm.ExtraMetaData[] extraMetaData) {
        this.extraMetaData = extraMetaData;
    }


    /**
     * Gets the pubChannels value for this Publication.
     * 
     * @return pubChannels
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getPubChannels() {
        return pubChannels;
    }


    /**
     * Sets the pubChannels value for this Publication.
     * 
     * @param pubChannels
     */
    public void setPubChannels(com.woodwing.enterprise.interfaces.services.adm.IdName[] pubChannels) {
        this.pubChannels = pubChannels;
    }


    /**
     * Gets the issues value for this Publication.
     * 
     * @return issues
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getIssues() {
        return issues;
    }


    /**
     * Sets the issues value for this Publication.
     * 
     * @param issues
     */
    public void setIssues(com.woodwing.enterprise.interfaces.services.adm.IdName[] issues) {
        this.issues = issues;
    }


    /**
     * Gets the editions value for this Publication.
     * 
     * @return editions
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getEditions() {
        return editions;
    }


    /**
     * Sets the editions value for this Publication.
     * 
     * @param editions
     */
    public void setEditions(com.woodwing.enterprise.interfaces.services.adm.IdName[] editions) {
        this.editions = editions;
    }


    /**
     * Gets the sections value for this Publication.
     * 
     * @return sections
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getSections() {
        return sections;
    }


    /**
     * Sets the sections value for this Publication.
     * 
     * @param sections
     */
    public void setSections(com.woodwing.enterprise.interfaces.services.adm.IdName[] sections) {
        this.sections = sections;
    }


    /**
     * Gets the statuses value for this Publication.
     * 
     * @return statuses
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getStatuses() {
        return statuses;
    }


    /**
     * Sets the statuses value for this Publication.
     * 
     * @param statuses
     */
    public void setStatuses(com.woodwing.enterprise.interfaces.services.adm.IdName[] statuses) {
        this.statuses = statuses;
    }


    /**
     * Gets the userGroups value for this Publication.
     * 
     * @return userGroups
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getUserGroups() {
        return userGroups;
    }


    /**
     * Sets the userGroups value for this Publication.
     * 
     * @param userGroups
     */
    public void setUserGroups(com.woodwing.enterprise.interfaces.services.adm.IdName[] userGroups) {
        this.userGroups = userGroups;
    }


    /**
     * Gets the adminGroups value for this Publication.
     * 
     * @return adminGroups
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getAdminGroups() {
        return adminGroups;
    }


    /**
     * Sets the adminGroups value for this Publication.
     * 
     * @param adminGroups
     */
    public void setAdminGroups(com.woodwing.enterprise.interfaces.services.adm.IdName[] adminGroups) {
        this.adminGroups = adminGroups;
    }


    /**
     * Gets the workflows value for this Publication.
     * 
     * @return workflows
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getWorkflows() {
        return workflows;
    }


    /**
     * Sets the workflows value for this Publication.
     * 
     * @param workflows
     */
    public void setWorkflows(com.woodwing.enterprise.interfaces.services.adm.IdName[] workflows) {
        this.workflows = workflows;
    }


    /**
     * Gets the routings value for this Publication.
     * 
     * @return routings
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getRoutings() {
        return routings;
    }


    /**
     * Sets the routings value for this Publication.
     * 
     * @param routings
     */
    public void setRoutings(com.woodwing.enterprise.interfaces.services.adm.IdName[] routings) {
        this.routings = routings;
    }


    /**
     * Gets the calculateDeadlines value for this Publication.
     * 
     * @return calculateDeadlines
     */
    public java.lang.Boolean getCalculateDeadlines() {
        return calculateDeadlines;
    }


    /**
     * Sets the calculateDeadlines value for this Publication.
     * 
     * @param calculateDeadlines
     */
    public void setCalculateDeadlines(java.lang.Boolean calculateDeadlines) {
        this.calculateDeadlines = calculateDeadlines;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Publication)) return false;
        Publication other = (Publication) obj;
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
            ((this.description==null && other.getDescription()==null) || 
             (this.description!=null &&
              this.description.equals(other.getDescription()))) &&
            ((this.sortOrder==null && other.getSortOrder()==null) || 
             (this.sortOrder!=null &&
              this.sortOrder.equals(other.getSortOrder()))) &&
            ((this.emailNotify==null && other.getEmailNotify()==null) || 
             (this.emailNotify!=null &&
              this.emailNotify.equals(other.getEmailNotify()))) &&
            ((this.reversedRead==null && other.getReversedRead()==null) || 
             (this.reversedRead!=null &&
              this.reversedRead.equals(other.getReversedRead()))) &&
            ((this.autoPurge==null && other.getAutoPurge()==null) || 
             (this.autoPurge!=null &&
              this.autoPurge.equals(other.getAutoPurge()))) &&
            ((this.defaultChannelId==null && other.getDefaultChannelId()==null) || 
             (this.defaultChannelId!=null &&
              this.defaultChannelId.equals(other.getDefaultChannelId()))) &&
            ((this.extraMetaData==null && other.getExtraMetaData()==null) || 
             (this.extraMetaData!=null &&
              java.util.Arrays.equals(this.extraMetaData, other.getExtraMetaData()))) &&
            ((this.pubChannels==null && other.getPubChannels()==null) || 
             (this.pubChannels!=null &&
              java.util.Arrays.equals(this.pubChannels, other.getPubChannels()))) &&
            ((this.issues==null && other.getIssues()==null) || 
             (this.issues!=null &&
              java.util.Arrays.equals(this.issues, other.getIssues()))) &&
            ((this.editions==null && other.getEditions()==null) || 
             (this.editions!=null &&
              java.util.Arrays.equals(this.editions, other.getEditions()))) &&
            ((this.sections==null && other.getSections()==null) || 
             (this.sections!=null &&
              java.util.Arrays.equals(this.sections, other.getSections()))) &&
            ((this.statuses==null && other.getStatuses()==null) || 
             (this.statuses!=null &&
              java.util.Arrays.equals(this.statuses, other.getStatuses()))) &&
            ((this.userGroups==null && other.getUserGroups()==null) || 
             (this.userGroups!=null &&
              java.util.Arrays.equals(this.userGroups, other.getUserGroups()))) &&
            ((this.adminGroups==null && other.getAdminGroups()==null) || 
             (this.adminGroups!=null &&
              java.util.Arrays.equals(this.adminGroups, other.getAdminGroups()))) &&
            ((this.workflows==null && other.getWorkflows()==null) || 
             (this.workflows!=null &&
              java.util.Arrays.equals(this.workflows, other.getWorkflows()))) &&
            ((this.routings==null && other.getRoutings()==null) || 
             (this.routings!=null &&
              java.util.Arrays.equals(this.routings, other.getRoutings()))) &&
            ((this.calculateDeadlines==null && other.getCalculateDeadlines()==null) || 
             (this.calculateDeadlines!=null &&
              this.calculateDeadlines.equals(other.getCalculateDeadlines())));
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
        if (getDescription() != null) {
            _hashCode += getDescription().hashCode();
        }
        if (getSortOrder() != null) {
            _hashCode += getSortOrder().hashCode();
        }
        if (getEmailNotify() != null) {
            _hashCode += getEmailNotify().hashCode();
        }
        if (getReversedRead() != null) {
            _hashCode += getReversedRead().hashCode();
        }
        if (getAutoPurge() != null) {
            _hashCode += getAutoPurge().hashCode();
        }
        if (getDefaultChannelId() != null) {
            _hashCode += getDefaultChannelId().hashCode();
        }
        if (getExtraMetaData() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getExtraMetaData());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getExtraMetaData(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
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
        if (getSections() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getSections());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getSections(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getStatuses() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getStatuses());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getStatuses(), i);
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
        if (getAdminGroups() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getAdminGroups());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getAdminGroups(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getWorkflows() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getWorkflows());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getWorkflows(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getRoutings() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRoutings());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRoutings(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getCalculateDeadlines() != null) {
            _hashCode += getCalculateDeadlines().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Publication.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Publication"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("id");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Id"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("description");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Description"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("sortOrder");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SortOrder"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("emailNotify");
        elemField.setXmlName(new javax.xml.namespace.QName("", "EmailNotify"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("reversedRead");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ReversedRead"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("autoPurge");
        elemField.setXmlName(new javax.xml.namespace.QName("", "AutoPurge"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("defaultChannelId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DefaultChannelId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("extraMetaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ExtraMetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ExtraMetaData"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ExtraMetaData"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pubChannels");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PubChannels"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IdName"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("issues");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Issues"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IdName"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("editions");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Editions"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IdName"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("sections");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Sections"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IdName"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("statuses");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Statuses"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IdName"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("userGroups");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UserGroups"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IdName"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("adminGroups");
        elemField.setXmlName(new javax.xml.namespace.QName("", "AdminGroups"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IdName"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("workflows");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Workflows"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IdName"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("routings");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Routings"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IdName"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("calculateDeadlines");
        elemField.setXmlName(new javax.xml.namespace.QName("", "CalculateDeadlines"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
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
