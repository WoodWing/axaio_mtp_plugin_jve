/**
 * GetUpdatesRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.dat;

public class GetUpdatesRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String updateID;

    private java.lang.String objectID;

    public GetUpdatesRequest() {
    }

    public GetUpdatesRequest(
           java.lang.String ticket,
           java.lang.String updateID,
           java.lang.String objectID) {
           this.ticket = ticket;
           this.updateID = updateID;
           this.objectID = objectID;
    }


    /**
     * Gets the ticket value for this GetUpdatesRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this GetUpdatesRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the updateID value for this GetUpdatesRequest.
     * 
     * @return updateID
     */
    public java.lang.String getUpdateID() {
        return updateID;
    }


    /**
     * Sets the updateID value for this GetUpdatesRequest.
     * 
     * @param updateID
     */
    public void setUpdateID(java.lang.String updateID) {
        this.updateID = updateID;
    }


    /**
     * Gets the objectID value for this GetUpdatesRequest.
     * 
     * @return objectID
     */
    public java.lang.String getObjectID() {
        return objectID;
    }


    /**
     * Sets the objectID value for this GetUpdatesRequest.
     * 
     * @param objectID
     */
    public void setObjectID(java.lang.String objectID) {
        this.objectID = objectID;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetUpdatesRequest)) return false;
        GetUpdatesRequest other = (GetUpdatesRequest) obj;
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
            ((this.updateID==null && other.getUpdateID()==null) || 
             (this.updateID!=null &&
              this.updateID.equals(other.getUpdateID()))) &&
            ((this.objectID==null && other.getObjectID()==null) || 
             (this.objectID!=null &&
              this.objectID.equals(other.getObjectID())));
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
        if (getUpdateID() != null) {
            _hashCode += getUpdateID().hashCode();
        }
        if (getObjectID() != null) {
            _hashCode += getObjectID().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetUpdatesRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", ">GetUpdatesRequest"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("updateID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UpdateID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objectID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ObjectID"));
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
