/**
 * RemoveGroupsFromUserRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class RemoveGroupsFromUserRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.math.BigInteger[] groupIds;

    private java.math.BigInteger userId;

    public RemoveGroupsFromUserRequest() {
    }

    public RemoveGroupsFromUserRequest(
           java.lang.String ticket,
           java.math.BigInteger[] groupIds,
           java.math.BigInteger userId) {
           this.ticket = ticket;
           this.groupIds = groupIds;
           this.userId = userId;
    }


    /**
     * Gets the ticket value for this RemoveGroupsFromUserRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this RemoveGroupsFromUserRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the groupIds value for this RemoveGroupsFromUserRequest.
     * 
     * @return groupIds
     */
    public java.math.BigInteger[] getGroupIds() {
        return groupIds;
    }


    /**
     * Sets the groupIds value for this RemoveGroupsFromUserRequest.
     * 
     * @param groupIds
     */
    public void setGroupIds(java.math.BigInteger[] groupIds) {
        this.groupIds = groupIds;
    }


    /**
     * Gets the userId value for this RemoveGroupsFromUserRequest.
     * 
     * @return userId
     */
    public java.math.BigInteger getUserId() {
        return userId;
    }


    /**
     * Sets the userId value for this RemoveGroupsFromUserRequest.
     * 
     * @param userId
     */
    public void setUserId(java.math.BigInteger userId) {
        this.userId = userId;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof RemoveGroupsFromUserRequest)) return false;
        RemoveGroupsFromUserRequest other = (RemoveGroupsFromUserRequest) obj;
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
            ((this.groupIds==null && other.getGroupIds()==null) || 
             (this.groupIds!=null &&
              java.util.Arrays.equals(this.groupIds, other.getGroupIds()))) &&
            ((this.userId==null && other.getUserId()==null) || 
             (this.userId!=null &&
              this.userId.equals(other.getUserId())));
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
        if (getGroupIds() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getGroupIds());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getGroupIds(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getUserId() != null) {
            _hashCode += getUserId().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(RemoveGroupsFromUserRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">RemoveGroupsFromUserRequest"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("groupIds");
        elemField.setXmlName(new javax.xml.namespace.QName("", "GroupIds"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Id"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Id"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("userId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UserId"));
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
