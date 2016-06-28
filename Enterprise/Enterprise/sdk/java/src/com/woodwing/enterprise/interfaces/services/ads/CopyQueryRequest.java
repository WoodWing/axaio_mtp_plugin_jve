/**
 * CopyQueryRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.ads;

public class CopyQueryRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String queryID;

    private java.lang.String targetID;

    private java.lang.String newName;

    private java.lang.String copyFields;

    public CopyQueryRequest() {
    }

    public CopyQueryRequest(
           java.lang.String ticket,
           java.lang.String queryID,
           java.lang.String targetID,
           java.lang.String newName,
           java.lang.String copyFields) {
           this.ticket = ticket;
           this.queryID = queryID;
           this.targetID = targetID;
           this.newName = newName;
           this.copyFields = copyFields;
    }


    /**
     * Gets the ticket value for this CopyQueryRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this CopyQueryRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the queryID value for this CopyQueryRequest.
     * 
     * @return queryID
     */
    public java.lang.String getQueryID() {
        return queryID;
    }


    /**
     * Sets the queryID value for this CopyQueryRequest.
     * 
     * @param queryID
     */
    public void setQueryID(java.lang.String queryID) {
        this.queryID = queryID;
    }


    /**
     * Gets the targetID value for this CopyQueryRequest.
     * 
     * @return targetID
     */
    public java.lang.String getTargetID() {
        return targetID;
    }


    /**
     * Sets the targetID value for this CopyQueryRequest.
     * 
     * @param targetID
     */
    public void setTargetID(java.lang.String targetID) {
        this.targetID = targetID;
    }


    /**
     * Gets the newName value for this CopyQueryRequest.
     * 
     * @return newName
     */
    public java.lang.String getNewName() {
        return newName;
    }


    /**
     * Sets the newName value for this CopyQueryRequest.
     * 
     * @param newName
     */
    public void setNewName(java.lang.String newName) {
        this.newName = newName;
    }


    /**
     * Gets the copyFields value for this CopyQueryRequest.
     * 
     * @return copyFields
     */
    public java.lang.String getCopyFields() {
        return copyFields;
    }


    /**
     * Sets the copyFields value for this CopyQueryRequest.
     * 
     * @param copyFields
     */
    public void setCopyFields(java.lang.String copyFields) {
        this.copyFields = copyFields;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof CopyQueryRequest)) return false;
        CopyQueryRequest other = (CopyQueryRequest) obj;
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
            ((this.queryID==null && other.getQueryID()==null) || 
             (this.queryID!=null &&
              this.queryID.equals(other.getQueryID()))) &&
            ((this.targetID==null && other.getTargetID()==null) || 
             (this.targetID!=null &&
              this.targetID.equals(other.getTargetID()))) &&
            ((this.newName==null && other.getNewName()==null) || 
             (this.newName!=null &&
              this.newName.equals(other.getNewName()))) &&
            ((this.copyFields==null && other.getCopyFields()==null) || 
             (this.copyFields!=null &&
              this.copyFields.equals(other.getCopyFields())));
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
        if (getQueryID() != null) {
            _hashCode += getQueryID().hashCode();
        }
        if (getTargetID() != null) {
            _hashCode += getTargetID().hashCode();
        }
        if (getNewName() != null) {
            _hashCode += getNewName().hashCode();
        }
        if (getCopyFields() != null) {
            _hashCode += getCopyFields().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(CopyQueryRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">CopyQueryRequest"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("queryID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "QueryID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("targetID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "TargetID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("newName");
        elemField.setXmlName(new javax.xml.namespace.QName("", "NewName"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("copyFields");
        elemField.setXmlName(new javax.xml.namespace.QName("", "CopyFields"));
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
