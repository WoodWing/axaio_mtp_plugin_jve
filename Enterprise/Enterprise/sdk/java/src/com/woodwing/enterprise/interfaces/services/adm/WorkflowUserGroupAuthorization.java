/**
 * WorkflowUserGroupAuthorization.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class WorkflowUserGroupAuthorization  implements java.io.Serializable {
    private java.math.BigInteger id;

    private java.math.BigInteger userGroupId;

    private java.math.BigInteger sectionId;

    private java.math.BigInteger statusId;

    private java.math.BigInteger accessProfileId;

    public WorkflowUserGroupAuthorization() {
    }

    public WorkflowUserGroupAuthorization(
           java.math.BigInteger id,
           java.math.BigInteger userGroupId,
           java.math.BigInteger sectionId,
           java.math.BigInteger statusId,
           java.math.BigInteger accessProfileId) {
           this.id = id;
           this.userGroupId = userGroupId;
           this.sectionId = sectionId;
           this.statusId = statusId;
           this.accessProfileId = accessProfileId;
    }


    /**
     * Gets the id value for this WorkflowUserGroupAuthorization.
     * 
     * @return id
     */
    public java.math.BigInteger getId() {
        return id;
    }


    /**
     * Sets the id value for this WorkflowUserGroupAuthorization.
     * 
     * @param id
     */
    public void setId(java.math.BigInteger id) {
        this.id = id;
    }


    /**
     * Gets the userGroupId value for this WorkflowUserGroupAuthorization.
     * 
     * @return userGroupId
     */
    public java.math.BigInteger getUserGroupId() {
        return userGroupId;
    }


    /**
     * Sets the userGroupId value for this WorkflowUserGroupAuthorization.
     * 
     * @param userGroupId
     */
    public void setUserGroupId(java.math.BigInteger userGroupId) {
        this.userGroupId = userGroupId;
    }


    /**
     * Gets the sectionId value for this WorkflowUserGroupAuthorization.
     * 
     * @return sectionId
     */
    public java.math.BigInteger getSectionId() {
        return sectionId;
    }


    /**
     * Sets the sectionId value for this WorkflowUserGroupAuthorization.
     * 
     * @param sectionId
     */
    public void setSectionId(java.math.BigInteger sectionId) {
        this.sectionId = sectionId;
    }


    /**
     * Gets the statusId value for this WorkflowUserGroupAuthorization.
     * 
     * @return statusId
     */
    public java.math.BigInteger getStatusId() {
        return statusId;
    }


    /**
     * Sets the statusId value for this WorkflowUserGroupAuthorization.
     * 
     * @param statusId
     */
    public void setStatusId(java.math.BigInteger statusId) {
        this.statusId = statusId;
    }


    /**
     * Gets the accessProfileId value for this WorkflowUserGroupAuthorization.
     * 
     * @return accessProfileId
     */
    public java.math.BigInteger getAccessProfileId() {
        return accessProfileId;
    }


    /**
     * Sets the accessProfileId value for this WorkflowUserGroupAuthorization.
     * 
     * @param accessProfileId
     */
    public void setAccessProfileId(java.math.BigInteger accessProfileId) {
        this.accessProfileId = accessProfileId;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof WorkflowUserGroupAuthorization)) return false;
        WorkflowUserGroupAuthorization other = (WorkflowUserGroupAuthorization) obj;
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
            ((this.userGroupId==null && other.getUserGroupId()==null) || 
             (this.userGroupId!=null &&
              this.userGroupId.equals(other.getUserGroupId()))) &&
            ((this.sectionId==null && other.getSectionId()==null) || 
             (this.sectionId!=null &&
              this.sectionId.equals(other.getSectionId()))) &&
            ((this.statusId==null && other.getStatusId()==null) || 
             (this.statusId!=null &&
              this.statusId.equals(other.getStatusId()))) &&
            ((this.accessProfileId==null && other.getAccessProfileId()==null) || 
             (this.accessProfileId!=null &&
              this.accessProfileId.equals(other.getAccessProfileId())));
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
        if (getUserGroupId() != null) {
            _hashCode += getUserGroupId().hashCode();
        }
        if (getSectionId() != null) {
            _hashCode += getSectionId().hashCode();
        }
        if (getStatusId() != null) {
            _hashCode += getStatusId().hashCode();
        }
        if (getAccessProfileId() != null) {
            _hashCode += getAccessProfileId().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(WorkflowUserGroupAuthorization.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "WorkflowUserGroupAuthorization"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("id");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Id"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("userGroupId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UserGroupId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("sectionId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SectionId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("statusId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "StatusId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("accessProfileId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "AccessProfileId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
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
