/**
 * CreateObjectLabels.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class CreateObjectLabels  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String objectId;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectLabel[] objectLabels;

    public CreateObjectLabels() {
    }

    public CreateObjectLabels(
           java.lang.String ticket,
           java.lang.String objectId,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectLabel[] objectLabels) {
           this.ticket = ticket;
           this.objectId = objectId;
           this.objectLabels = objectLabels;
    }


    /**
     * Gets the ticket value for this CreateObjectLabels.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this CreateObjectLabels.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the objectId value for this CreateObjectLabels.
     * 
     * @return objectId
     */
    public java.lang.String getObjectId() {
        return objectId;
    }


    /**
     * Sets the objectId value for this CreateObjectLabels.
     * 
     * @param objectId
     */
    public void setObjectId(java.lang.String objectId) {
        this.objectId = objectId;
    }


    /**
     * Gets the objectLabels value for this CreateObjectLabels.
     * 
     * @return objectLabels
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectLabel[] getObjectLabels() {
        return objectLabels;
    }


    /**
     * Sets the objectLabels value for this CreateObjectLabels.
     * 
     * @param objectLabels
     */
    public void setObjectLabels(com.woodwing.enterprise.interfaces.services.wfl.ObjectLabel[] objectLabels) {
        this.objectLabels = objectLabels;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof CreateObjectLabels)) return false;
        CreateObjectLabels other = (CreateObjectLabels) obj;
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
            ((this.objectId==null && other.getObjectId()==null) || 
             (this.objectId!=null &&
              this.objectId.equals(other.getObjectId()))) &&
            ((this.objectLabels==null && other.getObjectLabels()==null) || 
             (this.objectLabels!=null &&
              java.util.Arrays.equals(this.objectLabels, other.getObjectLabels())));
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
        if (getObjectId() != null) {
            _hashCode += getObjectId().hashCode();
        }
        if (getObjectLabels() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getObjectLabels());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getObjectLabels(), i);
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
        new org.apache.axis.description.TypeDesc(CreateObjectLabels.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectLabels"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objectId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ObjectId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objectLabels");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ObjectLabels"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectLabel"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ObjectLabel"));
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