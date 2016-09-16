/**
 * PubChannelInfo.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class PubChannelInfo  implements java.io.Serializable {
    private java.lang.String id;

    private java.lang.String name;

    private com.woodwing.enterprise.interfaces.services.wfl.IssueInfo[] issues;

    private com.woodwing.enterprise.interfaces.services.wfl.Edition[] editions;

    private java.lang.String currentIssue;

    private com.woodwing.enterprise.interfaces.services.wfl.PubChannelType type;

    private java.lang.Boolean directPublish;

    private java.lang.Boolean supportsForms;

    private java.lang.Boolean supportsCropping;

    public PubChannelInfo() {
    }

    public PubChannelInfo(
           java.lang.String id,
           java.lang.String name,
           com.woodwing.enterprise.interfaces.services.wfl.IssueInfo[] issues,
           com.woodwing.enterprise.interfaces.services.wfl.Edition[] editions,
           java.lang.String currentIssue,
           com.woodwing.enterprise.interfaces.services.wfl.PubChannelType type,
           java.lang.Boolean directPublish,
           java.lang.Boolean supportsForms,
           java.lang.Boolean supportsCropping) {
           this.id = id;
           this.name = name;
           this.issues = issues;
           this.editions = editions;
           this.currentIssue = currentIssue;
           this.type = type;
           this.directPublish = directPublish;
           this.supportsForms = supportsForms;
           this.supportsCropping = supportsCropping;
    }


    /**
     * Gets the id value for this PubChannelInfo.
     * 
     * @return id
     */
    public java.lang.String getId() {
        return id;
    }


    /**
     * Sets the id value for this PubChannelInfo.
     * 
     * @param id
     */
    public void setId(java.lang.String id) {
        this.id = id;
    }


    /**
     * Gets the name value for this PubChannelInfo.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this PubChannelInfo.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the issues value for this PubChannelInfo.
     * 
     * @return issues
     */
    public com.woodwing.enterprise.interfaces.services.wfl.IssueInfo[] getIssues() {
        return issues;
    }


    /**
     * Sets the issues value for this PubChannelInfo.
     * 
     * @param issues
     */
    public void setIssues(com.woodwing.enterprise.interfaces.services.wfl.IssueInfo[] issues) {
        this.issues = issues;
    }


    /**
     * Gets the editions value for this PubChannelInfo.
     * 
     * @return editions
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Edition[] getEditions() {
        return editions;
    }


    /**
     * Sets the editions value for this PubChannelInfo.
     * 
     * @param editions
     */
    public void setEditions(com.woodwing.enterprise.interfaces.services.wfl.Edition[] editions) {
        this.editions = editions;
    }


    /**
     * Gets the currentIssue value for this PubChannelInfo.
     * 
     * @return currentIssue
     */
    public java.lang.String getCurrentIssue() {
        return currentIssue;
    }


    /**
     * Sets the currentIssue value for this PubChannelInfo.
     * 
     * @param currentIssue
     */
    public void setCurrentIssue(java.lang.String currentIssue) {
        this.currentIssue = currentIssue;
    }


    /**
     * Gets the type value for this PubChannelInfo.
     * 
     * @return type
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PubChannelType getType() {
        return type;
    }


    /**
     * Sets the type value for this PubChannelInfo.
     * 
     * @param type
     */
    public void setType(com.woodwing.enterprise.interfaces.services.wfl.PubChannelType type) {
        this.type = type;
    }


    /**
     * Gets the directPublish value for this PubChannelInfo.
     * 
     * @return directPublish
     */
    public java.lang.Boolean getDirectPublish() {
        return directPublish;
    }


    /**
     * Sets the directPublish value for this PubChannelInfo.
     * 
     * @param directPublish
     */
    public void setDirectPublish(java.lang.Boolean directPublish) {
        this.directPublish = directPublish;
    }


    /**
     * Gets the supportsForms value for this PubChannelInfo.
     * 
     * @return supportsForms
     */
    public java.lang.Boolean getSupportsForms() {
        return supportsForms;
    }


    /**
     * Sets the supportsForms value for this PubChannelInfo.
     * 
     * @param supportsForms
     */
    public void setSupportsForms(java.lang.Boolean supportsForms) {
        this.supportsForms = supportsForms;
    }


    /**
     * Gets the supportsCropping value for this PubChannelInfo.
     * 
     * @return supportsCropping
     */
    public java.lang.Boolean getSupportsCropping() {
        return supportsCropping;
    }


    /**
     * Sets the supportsCropping value for this PubChannelInfo.
     * 
     * @param supportsCropping
     */
    public void setSupportsCropping(java.lang.Boolean supportsCropping) {
        this.supportsCropping = supportsCropping;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof PubChannelInfo)) return false;
        PubChannelInfo other = (PubChannelInfo) obj;
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
            ((this.editions==null && other.getEditions()==null) || 
             (this.editions!=null &&
              java.util.Arrays.equals(this.editions, other.getEditions()))) &&
            ((this.currentIssue==null && other.getCurrentIssue()==null) || 
             (this.currentIssue!=null &&
              this.currentIssue.equals(other.getCurrentIssue()))) &&
            ((this.type==null && other.getType()==null) || 
             (this.type!=null &&
              this.type.equals(other.getType()))) &&
            ((this.directPublish==null && other.getDirectPublish()==null) || 
             (this.directPublish!=null &&
              this.directPublish.equals(other.getDirectPublish()))) &&
            ((this.supportsForms==null && other.getSupportsForms()==null) || 
             (this.supportsForms!=null &&
              this.supportsForms.equals(other.getSupportsForms()))) &&
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
        if (getCurrentIssue() != null) {
            _hashCode += getCurrentIssue().hashCode();
        }
        if (getType() != null) {
            _hashCode += getType().hashCode();
        }
        if (getDirectPublish() != null) {
            _hashCode += getDirectPublish().hashCode();
        }
        if (getSupportsForms() != null) {
            _hashCode += getSupportsForms().hashCode();
        }
        if (getSupportsCropping() != null) {
            _hashCode += getSupportsCropping().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(PubChannelInfo.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PubChannelInfo"));
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
        elemField.setFieldName("editions");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Editions"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Edition"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Edition"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("currentIssue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "CurrentIssue"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("type");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Type"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PubChannelType"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("directPublish");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DirectPublish"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
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
