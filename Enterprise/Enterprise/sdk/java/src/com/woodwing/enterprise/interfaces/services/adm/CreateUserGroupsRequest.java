/**
 * CreateUserGroupsRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class CreateUserGroupsRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private com.woodwing.enterprise.interfaces.services.adm.Mode[] requestModes;

    private com.woodwing.enterprise.interfaces.services.adm.UserGroup[] userGroups;

    public CreateUserGroupsRequest() {
    }

    public CreateUserGroupsRequest(
           java.lang.String ticket,
           com.woodwing.enterprise.interfaces.services.adm.Mode[] requestModes,
           com.woodwing.enterprise.interfaces.services.adm.UserGroup[] userGroups) {
           this.ticket = ticket;
           this.requestModes = requestModes;
           this.userGroups = userGroups;
    }


    /**
     * Gets the ticket value for this CreateUserGroupsRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this CreateUserGroupsRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the requestModes value for this CreateUserGroupsRequest.
     * 
     * @return requestModes
     */
    public com.woodwing.enterprise.interfaces.services.adm.Mode[] getRequestModes() {
        return requestModes;
    }


    /**
     * Sets the requestModes value for this CreateUserGroupsRequest.
     * 
     * @param requestModes
     */
    public void setRequestModes(com.woodwing.enterprise.interfaces.services.adm.Mode[] requestModes) {
        this.requestModes = requestModes;
    }


    /**
     * Gets the userGroups value for this CreateUserGroupsRequest.
     * 
     * @return userGroups
     */
    public com.woodwing.enterprise.interfaces.services.adm.UserGroup[] getUserGroups() {
        return userGroups;
    }


    /**
     * Sets the userGroups value for this CreateUserGroupsRequest.
     * 
     * @param userGroups
     */
    public void setUserGroups(com.woodwing.enterprise.interfaces.services.adm.UserGroup[] userGroups) {
        this.userGroups = userGroups;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof CreateUserGroupsRequest)) return false;
        CreateUserGroupsRequest other = (CreateUserGroupsRequest) obj;
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
            ((this.userGroups==null && other.getUserGroups()==null) || 
             (this.userGroups!=null &&
              java.util.Arrays.equals(this.userGroups, other.getUserGroups())));
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
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(CreateUserGroupsRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreateUserGroupsRequest"));
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
        elemField.setFieldName("userGroups");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UserGroups"));
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
