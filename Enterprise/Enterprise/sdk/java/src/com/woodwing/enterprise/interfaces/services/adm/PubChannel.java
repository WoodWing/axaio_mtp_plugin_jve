/**
 * PubChannel.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class PubChannel  implements java.io.Serializable {
    private java.math.BigInteger id;

    private java.lang.String name;

    private com.woodwing.enterprise.interfaces.services.adm.PubChannelType type;

    private java.lang.String description;

    private java.lang.String publishSystem;

    private java.lang.String publishSystemId;

    private java.math.BigInteger currentIssueId;

    private java.lang.String suggestionProvider;

    private com.woodwing.enterprise.interfaces.services.adm.ExtraMetaData[] extraMetaData;

    private java.lang.Boolean directPublish;

    private java.lang.Boolean supportsForms;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] issues;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] editions;

    private java.lang.Boolean supportsCropping;

    public PubChannel() {
    }

    public PubChannel(
           java.math.BigInteger id,
           java.lang.String name,
           com.woodwing.enterprise.interfaces.services.adm.PubChannelType type,
           java.lang.String description,
           java.lang.String publishSystem,
           java.lang.String publishSystemId,
           java.math.BigInteger currentIssueId,
           java.lang.String suggestionProvider,
           com.woodwing.enterprise.interfaces.services.adm.ExtraMetaData[] extraMetaData,
           java.lang.Boolean directPublish,
           java.lang.Boolean supportsForms,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] issues,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] editions,
           java.lang.Boolean supportsCropping) {
           this.id = id;
           this.name = name;
           this.type = type;
           this.description = description;
           this.publishSystem = publishSystem;
           this.publishSystemId = publishSystemId;
           this.currentIssueId = currentIssueId;
           this.suggestionProvider = suggestionProvider;
           this.extraMetaData = extraMetaData;
           this.directPublish = directPublish;
           this.supportsForms = supportsForms;
           this.issues = issues;
           this.editions = editions;
           this.supportsCropping = supportsCropping;
    }


    /**
     * Gets the id value for this PubChannel.
     * 
     * @return id
     */
    public java.math.BigInteger getId() {
        return id;
    }


    /**
     * Sets the id value for this PubChannel.
     * 
     * @param id
     */
    public void setId(java.math.BigInteger id) {
        this.id = id;
    }


    /**
     * Gets the name value for this PubChannel.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this PubChannel.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the type value for this PubChannel.
     * 
     * @return type
     */
    public com.woodwing.enterprise.interfaces.services.adm.PubChannelType getType() {
        return type;
    }


    /**
     * Sets the type value for this PubChannel.
     * 
     * @param type
     */
    public void setType(com.woodwing.enterprise.interfaces.services.adm.PubChannelType type) {
        this.type = type;
    }


    /**
     * Gets the description value for this PubChannel.
     * 
     * @return description
     */
    public java.lang.String getDescription() {
        return description;
    }


    /**
     * Sets the description value for this PubChannel.
     * 
     * @param description
     */
    public void setDescription(java.lang.String description) {
        this.description = description;
    }


    /**
     * Gets the publishSystem value for this PubChannel.
     * 
     * @return publishSystem
     */
    public java.lang.String getPublishSystem() {
        return publishSystem;
    }


    /**
     * Sets the publishSystem value for this PubChannel.
     * 
     * @param publishSystem
     */
    public void setPublishSystem(java.lang.String publishSystem) {
        this.publishSystem = publishSystem;
    }


    /**
     * Gets the publishSystemId value for this PubChannel.
     * 
     * @return publishSystemId
     */
    public java.lang.String getPublishSystemId() {
        return publishSystemId;
    }


    /**
     * Sets the publishSystemId value for this PubChannel.
     * 
     * @param publishSystemId
     */
    public void setPublishSystemId(java.lang.String publishSystemId) {
        this.publishSystemId = publishSystemId;
    }


    /**
     * Gets the currentIssueId value for this PubChannel.
     * 
     * @return currentIssueId
     */
    public java.math.BigInteger getCurrentIssueId() {
        return currentIssueId;
    }


    /**
     * Sets the currentIssueId value for this PubChannel.
     * 
     * @param currentIssueId
     */
    public void setCurrentIssueId(java.math.BigInteger currentIssueId) {
        this.currentIssueId = currentIssueId;
    }


    /**
     * Gets the suggestionProvider value for this PubChannel.
     * 
     * @return suggestionProvider
     */
    public java.lang.String getSuggestionProvider() {
        return suggestionProvider;
    }


    /**
     * Sets the suggestionProvider value for this PubChannel.
     * 
     * @param suggestionProvider
     */
    public void setSuggestionProvider(java.lang.String suggestionProvider) {
        this.suggestionProvider = suggestionProvider;
    }


    /**
     * Gets the extraMetaData value for this PubChannel.
     * 
     * @return extraMetaData
     */
    public com.woodwing.enterprise.interfaces.services.adm.ExtraMetaData[] getExtraMetaData() {
        return extraMetaData;
    }


    /**
     * Sets the extraMetaData value for this PubChannel.
     * 
     * @param extraMetaData
     */
    public void setExtraMetaData(com.woodwing.enterprise.interfaces.services.adm.ExtraMetaData[] extraMetaData) {
        this.extraMetaData = extraMetaData;
    }


    /**
     * Gets the directPublish value for this PubChannel.
     * 
     * @return directPublish
     */
    public java.lang.Boolean getDirectPublish() {
        return directPublish;
    }


    /**
     * Sets the directPublish value for this PubChannel.
     * 
     * @param directPublish
     */
    public void setDirectPublish(java.lang.Boolean directPublish) {
        this.directPublish = directPublish;
    }


    /**
     * Gets the supportsForms value for this PubChannel.
     * 
     * @return supportsForms
     */
    public java.lang.Boolean getSupportsForms() {
        return supportsForms;
    }


    /**
     * Sets the supportsForms value for this PubChannel.
     * 
     * @param supportsForms
     */
    public void setSupportsForms(java.lang.Boolean supportsForms) {
        this.supportsForms = supportsForms;
    }


    /**
     * Gets the issues value for this PubChannel.
     * 
     * @return issues
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getIssues() {
        return issues;
    }


    /**
     * Sets the issues value for this PubChannel.
     * 
     * @param issues
     */
    public void setIssues(com.woodwing.enterprise.interfaces.services.adm.IdName[] issues) {
        this.issues = issues;
    }


    /**
     * Gets the editions value for this PubChannel.
     * 
     * @return editions
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getEditions() {
        return editions;
    }


    /**
     * Sets the editions value for this PubChannel.
     * 
     * @param editions
     */
    public void setEditions(com.woodwing.enterprise.interfaces.services.adm.IdName[] editions) {
        this.editions = editions;
    }


    /**
     * Gets the supportsCropping value for this PubChannel.
     * 
     * @return supportsCropping
     */
    public java.lang.Boolean getSupportsCropping() {
        return supportsCropping;
    }


    /**
     * Sets the supportsCropping value for this PubChannel.
     * 
     * @param supportsCropping
     */
    public void setSupportsCropping(java.lang.Boolean supportsCropping) {
        this.supportsCropping = supportsCropping;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof PubChannel)) return false;
        PubChannel other = (PubChannel) obj;
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
            ((this.type==null && other.getType()==null) || 
             (this.type!=null &&
              this.type.equals(other.getType()))) &&
            ((this.description==null && other.getDescription()==null) || 
             (this.description!=null &&
              this.description.equals(other.getDescription()))) &&
            ((this.publishSystem==null && other.getPublishSystem()==null) || 
             (this.publishSystem!=null &&
              this.publishSystem.equals(other.getPublishSystem()))) &&
            ((this.publishSystemId==null && other.getPublishSystemId()==null) || 
             (this.publishSystemId!=null &&
              this.publishSystemId.equals(other.getPublishSystemId()))) &&
            ((this.currentIssueId==null && other.getCurrentIssueId()==null) || 
             (this.currentIssueId!=null &&
              this.currentIssueId.equals(other.getCurrentIssueId()))) &&
            ((this.suggestionProvider==null && other.getSuggestionProvider()==null) || 
             (this.suggestionProvider!=null &&
              this.suggestionProvider.equals(other.getSuggestionProvider()))) &&
            ((this.extraMetaData==null && other.getExtraMetaData()==null) || 
             (this.extraMetaData!=null &&
              java.util.Arrays.equals(this.extraMetaData, other.getExtraMetaData()))) &&
            ((this.directPublish==null && other.getDirectPublish()==null) || 
             (this.directPublish!=null &&
              this.directPublish.equals(other.getDirectPublish()))) &&
            ((this.supportsForms==null && other.getSupportsForms()==null) || 
             (this.supportsForms!=null &&
              this.supportsForms.equals(other.getSupportsForms()))) &&
            ((this.issues==null && other.getIssues()==null) || 
             (this.issues!=null &&
              java.util.Arrays.equals(this.issues, other.getIssues()))) &&
            ((this.editions==null && other.getEditions()==null) || 
             (this.editions!=null &&
              java.util.Arrays.equals(this.editions, other.getEditions()))) &&
            ((this.supportsCropping==null && other.getSupportsCropping()==null) || 
             (this.supportsCropping!=null &&
              this.supportsCropping.equals(other.getSupportsCropping())));
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
        if (getType() != null) {
            _hashCode += getType().hashCode();
        }
        if (getDescription() != null) {
            _hashCode += getDescription().hashCode();
        }
        if (getPublishSystem() != null) {
            _hashCode += getPublishSystem().hashCode();
        }
        if (getPublishSystemId() != null) {
            _hashCode += getPublishSystemId().hashCode();
        }
        if (getCurrentIssueId() != null) {
            _hashCode += getCurrentIssueId().hashCode();
        }
        if (getSuggestionProvider() != null) {
            _hashCode += getSuggestionProvider().hashCode();
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
        if (getDirectPublish() != null) {
            _hashCode += getDirectPublish().hashCode();
        }
        if (getSupportsForms() != null) {
            _hashCode += getSupportsForms().hashCode();
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
        if (getSupportsCropping() != null) {
            _hashCode += getSupportsCropping().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(PubChannel.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "PubChannel"));
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
        elemField.setFieldName("type");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Type"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "PubChannelType"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("description");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Description"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishSystem");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishSystem"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishSystemId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishSystemId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("currentIssueId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "CurrentIssueId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("suggestionProvider");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SuggestionProvider"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("extraMetaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ExtraMetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ExtraMetaData"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ExtraMetaData"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("directPublish");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DirectPublish"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("supportsForms");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SupportsForms"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
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
        elemField.setFieldName("supportsCropping");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SupportsCropping"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
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
