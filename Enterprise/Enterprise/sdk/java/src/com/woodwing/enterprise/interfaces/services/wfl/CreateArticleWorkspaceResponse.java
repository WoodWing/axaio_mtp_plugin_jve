/**
 * CreateArticleWorkspaceResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class CreateArticleWorkspaceResponse  implements java.io.Serializable {
    private java.lang.String workspaceId;

    public CreateArticleWorkspaceResponse() {
    }

    public CreateArticleWorkspaceResponse(
           java.lang.String workspaceId) {
           this.workspaceId = workspaceId;
    }


    /**
     * Gets the workspaceId value for this CreateArticleWorkspaceResponse.
     * 
     * @return workspaceId
     */
    public java.lang.String getWorkspaceId() {
        return workspaceId;
    }


    /**
     * Sets the workspaceId value for this CreateArticleWorkspaceResponse.
     * 
     * @param workspaceId
     */
    public void setWorkspaceId(java.lang.String workspaceId) {
        this.workspaceId = workspaceId;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof CreateArticleWorkspaceResponse)) return false;
        CreateArticleWorkspaceResponse other = (CreateArticleWorkspaceResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.workspaceId==null && other.getWorkspaceId()==null) || 
             (this.workspaceId!=null &&
              this.workspaceId.equals(other.getWorkspaceId())));
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
        if (getWorkspaceId() != null) {
            _hashCode += getWorkspaceId().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(CreateArticleWorkspaceResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">CreateArticleWorkspaceResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("workspaceId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "WorkspaceId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
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
