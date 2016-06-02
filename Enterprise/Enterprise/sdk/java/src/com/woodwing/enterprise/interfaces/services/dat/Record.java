/**
 * Record.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.dat;

public class Record  implements java.io.Serializable {
    private java.lang.String ID;

    private com.woodwing.enterprise.interfaces.services.dat.UpdateType updateType;

    private com.woodwing.enterprise.interfaces.services.dat.ResponseType updateResponse;

    private boolean hidden;

    private com.woodwing.enterprise.interfaces.services.dat.RecordField[] fields;

    public Record() {
    }

    public Record(
           java.lang.String ID,
           com.woodwing.enterprise.interfaces.services.dat.UpdateType updateType,
           com.woodwing.enterprise.interfaces.services.dat.ResponseType updateResponse,
           boolean hidden,
           com.woodwing.enterprise.interfaces.services.dat.RecordField[] fields) {
           this.ID = ID;
           this.updateType = updateType;
           this.updateResponse = updateResponse;
           this.hidden = hidden;
           this.fields = fields;
    }


    /**
     * Gets the ID value for this Record.
     * 
     * @return ID
     */
    public java.lang.String getID() {
        return ID;
    }


    /**
     * Sets the ID value for this Record.
     * 
     * @param ID
     */
    public void setID(java.lang.String ID) {
        this.ID = ID;
    }


    /**
     * Gets the updateType value for this Record.
     * 
     * @return updateType
     */
    public com.woodwing.enterprise.interfaces.services.dat.UpdateType getUpdateType() {
        return updateType;
    }


    /**
     * Sets the updateType value for this Record.
     * 
     * @param updateType
     */
    public void setUpdateType(com.woodwing.enterprise.interfaces.services.dat.UpdateType updateType) {
        this.updateType = updateType;
    }


    /**
     * Gets the updateResponse value for this Record.
     * 
     * @return updateResponse
     */
    public com.woodwing.enterprise.interfaces.services.dat.ResponseType getUpdateResponse() {
        return updateResponse;
    }


    /**
     * Sets the updateResponse value for this Record.
     * 
     * @param updateResponse
     */
    public void setUpdateResponse(com.woodwing.enterprise.interfaces.services.dat.ResponseType updateResponse) {
        this.updateResponse = updateResponse;
    }


    /**
     * Gets the hidden value for this Record.
     * 
     * @return hidden
     */
    public boolean isHidden() {
        return hidden;
    }


    /**
     * Sets the hidden value for this Record.
     * 
     * @param hidden
     */
    public void setHidden(boolean hidden) {
        this.hidden = hidden;
    }


    /**
     * Gets the fields value for this Record.
     * 
     * @return fields
     */
    public com.woodwing.enterprise.interfaces.services.dat.RecordField[] getFields() {
        return fields;
    }


    /**
     * Sets the fields value for this Record.
     * 
     * @param fields
     */
    public void setFields(com.woodwing.enterprise.interfaces.services.dat.RecordField[] fields) {
        this.fields = fields;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Record)) return false;
        Record other = (Record) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ID==null && other.getID()==null) || 
             (this.ID!=null &&
              this.ID.equals(other.getID()))) &&
            ((this.updateType==null && other.getUpdateType()==null) || 
             (this.updateType!=null &&
              this.updateType.equals(other.getUpdateType()))) &&
            ((this.updateResponse==null && other.getUpdateResponse()==null) || 
             (this.updateResponse!=null &&
              this.updateResponse.equals(other.getUpdateResponse()))) &&
            this.hidden == other.isHidden() &&
            ((this.fields==null && other.getFields()==null) || 
             (this.fields!=null &&
              java.util.Arrays.equals(this.fields, other.getFields())));
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
        if (getID() != null) {
            _hashCode += getID().hashCode();
        }
        if (getUpdateType() != null) {
            _hashCode += getUpdateType().hashCode();
        }
        if (getUpdateResponse() != null) {
            _hashCode += getUpdateResponse().hashCode();
        }
        _hashCode += (isHidden() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        if (getFields() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getFields());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getFields(), i);
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
        new org.apache.axis.description.TypeDesc(Record.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "Record"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("updateType");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UpdateType"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "UpdateType"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("updateResponse");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UpdateResponse"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "ResponseType"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("hidden");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Hidden"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("fields");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Fields"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "RecordField"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "RecordField"));
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
