/**
 * TemplateObjectAccess.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class TemplateObjectAccess  implements java.io.Serializable {
    private java.math.BigInteger templateObjectId;

    private java.math.BigInteger userGroupId;

    public TemplateObjectAccess() {
    }

    public TemplateObjectAccess(
           java.math.BigInteger templateObjectId,
           java.math.BigInteger userGroupId) {
           this.templateObjectId = templateObjectId;
           this.userGroupId = userGroupId;
    }


    /**
     * Gets the templateObjectId value for this TemplateObjectAccess.
     * 
     * @return templateObjectId
     */
    public java.math.BigInteger getTemplateObjectId() {
        return templateObjectId;
    }


    /**
     * Sets the templateObjectId value for this TemplateObjectAccess.
     * 
     * @param templateObjectId
     */
    public void setTemplateObjectId(java.math.BigInteger templateObjectId) {
        this.templateObjectId = templateObjectId;
    }


    /**
     * Gets the userGroupId value for this TemplateObjectAccess.
     * 
     * @return userGroupId
     */
    public java.math.BigInteger getUserGroupId() {
        return userGroupId;
    }


    /**
     * Sets the userGroupId value for this TemplateObjectAccess.
     * 
     * @param userGroupId
     */
    public void setUserGroupId(java.math.BigInteger userGroupId) {
        this.userGroupId = userGroupId;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof TemplateObjectAccess)) return false;
        TemplateObjectAccess other = (TemplateObjectAccess) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.templateObjectId==null && other.getTemplateObjectId()==null) || 
             (this.templateObjectId!=null &&
              this.templateObjectId.equals(other.getTemplateObjectId()))) &&
            ((this.userGroupId==null && other.getUserGroupId()==null) || 
             (this.userGroupId!=null &&
              this.userGroupId.equals(other.getUserGroupId())));
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
        if (getTemplateObjectId() != null) {
            _hashCode += getTemplateObjectId().hashCode();
        }
        if (getUserGroupId() != null) {
            _hashCode += getUserGroupId().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(TemplateObjectAccess.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "TemplateObjectAccess"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("templateObjectId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "TemplateObjectId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("userGroupId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UserGroupId"));
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
