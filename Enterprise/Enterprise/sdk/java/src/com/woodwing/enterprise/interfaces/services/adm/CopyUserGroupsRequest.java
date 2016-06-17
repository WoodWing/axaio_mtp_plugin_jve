/**
 * CopyUserGroupsRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class CopyUserGroupsRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private com.woodwing.enterprise.interfaces.services.adm.Mode[] requestModes;

    private java.math.BigInteger sourceGroupId;

    private com.woodwing.enterprise.interfaces.services.adm.UserGroup[] targetGroups;

    public CopyUserGroupsRequest() {
    }

    public CopyUserGroupsRequest(
           java.lang.String ticket,
           com.woodwing.enterprise.interfaces.services.adm.Mode[] requestModes,
           java.math.BigInteger sourceGroupId,
           com.woodwing.enterprise.interfaces.services.adm.UserGroup[] targetGroups) {
           this.ticket = ticket;
           this.requestModes = requestModes;
           this.sourceGroupId = sourceGroupId;
           this.targetGroups = targetGroups;
    }


    /**
     * Gets the ticket value for this CopyUserGroupsRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this CopyUserGroupsRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the requestModes value for this CopyUserGroupsRequest.
     * 
     * @return requestModes
     */
    public com.woodwing.enterprise.interfaces.services.adm.Mode[] getRequestModes() {
        return requestModes;
    }


    /**
     * Sets the requestModes value for this CopyUserGroupsRequest.
     * 
     * @param requestModes
     */
    public void setRequestModes(com.woodwing.enterprise.interfaces.services.adm.Mode[] requestModes) {
        this.requestModes = requestModes;
    }


    /**
     * Gets the sourceGroupId value for this CopyUserGroupsRequest.
     * 
     * @return sourceGroupId
     */
    public java.math.BigInteger getSourceGroupId() {
        return sourceGroupId;
    }


    /**
     * Sets the sourceGroupId value for this CopyUserGroupsRequest.
     * 
     * @param sourceGroupId
     */
    public void setSourceGroupId(java.math.BigInteger sourceGroupId) {
        this.sourceGroupId = sourceGroupId;
    }


    /**
     * Gets the targetGroups value for this CopyUserGroupsRequest.
     * 
     * @return targetGroups
     */
    public com.woodwing.enterprise.interfaces.services.adm.UserGroup[] getTargetGroups() {
        return targetGroups;
    }


    /**
     * Sets the targetGroups value for this CopyUserGroupsRequest.
     * 
     * @param targetGroups
     */
    public void setTargetGroups(com.woodwing.enterprise.interfaces.services.adm.UserGroup[] targetGroups) {
        this.targetGroups = targetGroups;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof CopyUserGroupsRequest)) return false;
        CopyUserGroupsRequest other = (CopyUserGroupsRequest) obj;
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
            ((this.requestModes==null && other.getRequestModes()==null) || 
             (this.requestModes!=null &&
              java.util.Arrays.equals(this.requestModes, other.getRequestModes()))) &&
            ((this.sourceGroupId==null && other.getSourceGroupId()==null) || 
             (this.sourceGroupId!=null &&
              this.sourceGroupId.equals(other.getSourceGroupId()))) &&
            ((this.targetGroups==null && other.getTargetGroups()==null) || 
             (this.targetGroups!=null &&
              java.util.Arrays.equals(this.targetGroups, other.getTargetGroups())));
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
        if (getRequestModes() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRequestModes());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRequestModes(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getSourceGroupId() != null) {
            _hashCode += getSourceGroupId().hashCode();
        }
        if (getTargetGroups() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getTargetGroups());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getTargetGroups(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(CopyUserGroupsRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CopyUserGroupsRequest"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestModes");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestModes"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Mode"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Mode"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("sourceGroupId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SourceGroupId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("targetGroups");
        elemField.setXmlName(new javax.xml.namespace.QName("", "TargetGroups"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "UserGroup"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "UserGroup"));
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
