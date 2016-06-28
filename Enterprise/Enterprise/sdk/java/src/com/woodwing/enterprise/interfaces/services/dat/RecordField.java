/**
 * RecordField.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.dat;

public class RecordField  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.dat.UpdateType updateType;

    private com.woodwing.enterprise.interfaces.services.dat.ResponseType updateResponse;

    private boolean readOnly;

    private boolean priority;

    private java.lang.String name;

    private java.lang.String strValue;

    private java.lang.Integer intValue;

    private com.woodwing.enterprise.interfaces.services.dat.List[] listValue;

    private com.woodwing.enterprise.interfaces.services.dat.List[] imageListValue;

    private com.woodwing.enterprise.interfaces.services.dat.Attribute[] attributes;

    public RecordField() {
    }

    public RecordField(
           com.woodwing.enterprise.interfaces.services.dat.UpdateType updateType,
           com.woodwing.enterprise.interfaces.services.dat.ResponseType updateResponse,
           boolean readOnly,
           boolean priority,
           java.lang.String name,
           java.lang.String strValue,
           java.lang.Integer intValue,
           com.woodwing.enterprise.interfaces.services.dat.List[] listValue,
           com.woodwing.enterprise.interfaces.services.dat.List[] imageListValue,
           com.woodwing.enterprise.interfaces.services.dat.Attribute[] attributes) {
           this.updateType = updateType;
           this.updateResponse = updateResponse;
           this.readOnly = readOnly;
           this.priority = priority;
           this.name = name;
           this.strValue = strValue;
           this.intValue = intValue;
           this.listValue = listValue;
           this.imageListValue = imageListValue;
           this.attributes = attributes;
    }


    /**
     * Gets the updateType value for this RecordField.
     * 
     * @return updateType
     */
    public com.woodwing.enterprise.interfaces.services.dat.UpdateType getUpdateType() {
        return updateType;
    }


    /**
     * Sets the updateType value for this RecordField.
     * 
     * @param updateType
     */
    public void setUpdateType(com.woodwing.enterprise.interfaces.services.dat.UpdateType updateType) {
        this.updateType = updateType;
    }


    /**
     * Gets the updateResponse value for this RecordField.
     * 
     * @return updateResponse
     */
    public com.woodwing.enterprise.interfaces.services.dat.ResponseType getUpdateResponse() {
        return updateResponse;
    }


    /**
     * Sets the updateResponse value for this RecordField.
     * 
     * @param updateResponse
     */
    public void setUpdateResponse(com.woodwing.enterprise.interfaces.services.dat.ResponseType updateResponse) {
        this.updateResponse = updateResponse;
    }


    /**
     * Gets the readOnly value for this RecordField.
     * 
     * @return readOnly
     */
    public boolean isReadOnly() {
        return readOnly;
    }


    /**
     * Sets the readOnly value for this RecordField.
     * 
     * @param readOnly
     */
    public void setReadOnly(boolean readOnly) {
        this.readOnly = readOnly;
    }


    /**
     * Gets the priority value for this RecordField.
     * 
     * @return priority
     */
    public boolean isPriority() {
        return priority;
    }


    /**
     * Sets the priority value for this RecordField.
     * 
     * @param priority
     */
    public void setPriority(boolean priority) {
        this.priority = priority;
    }


    /**
     * Gets the name value for this RecordField.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this RecordField.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the strValue value for this RecordField.
     * 
     * @return strValue
     */
    public java.lang.String getStrValue() {
        return strValue;
    }


    /**
     * Sets the strValue value for this RecordField.
     * 
     * @param strValue
     */
    public void setStrValue(java.lang.String strValue) {
        this.strValue = strValue;
    }


    /**
     * Gets the intValue value for this RecordField.
     * 
     * @return intValue
     */
    public java.lang.Integer getIntValue() {
        return intValue;
    }


    /**
     * Sets the intValue value for this RecordField.
     * 
     * @param intValue
     */
    public void setIntValue(java.lang.Integer intValue) {
        this.intValue = intValue;
    }


    /**
     * Gets the listValue value for this RecordField.
     * 
     * @return listValue
     */
    public com.woodwing.enterprise.interfaces.services.dat.List[] getListValue() {
        return listValue;
    }


    /**
     * Sets the listValue value for this RecordField.
     * 
     * @param listValue
     */
    public void setListValue(com.woodwing.enterprise.interfaces.services.dat.List[] listValue) {
        this.listValue = listValue;
    }


    /**
     * Gets the imageListValue value for this RecordField.
     * 
     * @return imageListValue
     */
    public com.woodwing.enterprise.interfaces.services.dat.List[] getImageListValue() {
        return imageListValue;
    }


    /**
     * Sets the imageListValue value for this RecordField.
     * 
     * @param imageListValue
     */
    public void setImageListValue(com.woodwing.enterprise.interfaces.services.dat.List[] imageListValue) {
        this.imageListValue = imageListValue;
    }


    /**
     * Gets the attributes value for this RecordField.
     * 
     * @return attributes
     */
    public com.woodwing.enterprise.interfaces.services.dat.Attribute[] getAttributes() {
        return attributes;
    }


    /**
     * Sets the attributes value for this RecordField.
     * 
     * @param attributes
     */
    public void setAttributes(com.woodwing.enterprise.interfaces.services.dat.Attribute[] attributes) {
        this.attributes = attributes;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof RecordField)) return false;
        RecordField other = (RecordField) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.updateType==null && other.getUpdateType()==null) || 
             (this.updateType!=null &&
              this.updateType.equals(other.getUpdateType()))) &&
            ((this.updateResponse==null && other.getUpdateResponse()==null) || 
             (this.updateResponse!=null &&
              this.updateResponse.equals(other.getUpdateResponse()))) &&
            this.readOnly == other.isReadOnly() &&
            this.priority == other.isPriority() &&
            ((this.name==null && other.getName()==null) || 
             (this.name!=null &&
              this.name.equals(other.getName()))) &&
            ((this.strValue==null && other.getStrValue()==null) || 
             (this.strValue!=null &&
              this.strValue.equals(other.getStrValue()))) &&
            ((this.intValue==null && other.getIntValue()==null) || 
             (this.intValue!=null &&
              this.intValue.equals(other.getIntValue()))) &&
            ((this.listValue==null && other.getListValue()==null) || 
             (this.listValue!=null &&
              java.util.Arrays.equals(this.listValue, other.getListValue()))) &&
            ((this.imageListValue==null && other.getImageListValue()==null) || 
             (this.imageListValue!=null &&
              java.util.Arrays.equals(this.imageListValue, other.getImageListValue()))) &&
            ((this.attributes==null && other.getAttributes()==null) || 
             (this.attributes!=null &&
              java.util.Arrays.equals(this.attributes, other.getAttributes())));
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
        if (getUpdateType() != null) {
            _hashCode += getUpdateType().hashCode();
        }
        if (getUpdateResponse() != null) {
            _hashCode += getUpdateResponse().hashCode();
        }
        _hashCode += (isReadOnly() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        _hashCode += (isPriority() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        if (getName() != null) {
            _hashCode += getName().hashCode();
        }
        if (getStrValue() != null) {
            _hashCode += getStrValue().hashCode();
        }
        if (getIntValue() != null) {
            _hashCode += getIntValue().hashCode();
        }
        if (getListValue() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getListValue());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getListValue(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getImageListValue() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getImageListValue());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getImageListValue(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getAttributes() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getAttributes());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getAttributes(), i);
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
        new org.apache.axis.description.TypeDesc(RecordField.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "RecordField"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
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
        elemField.setFieldName("readOnly");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ReadOnly"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("priority");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Priority"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("strValue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "StrValue"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("intValue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "IntValue"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "int"));
        elemField.setMinOccurs(0);
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("listValue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ListValue"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "List"));
        elemField.setMinOccurs(0);
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "List"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("imageListValue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ImageListValue"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "List"));
        elemField.setMinOccurs(0);
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "List"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("attributes");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Attributes"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "Attribute"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Attribute"));
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
