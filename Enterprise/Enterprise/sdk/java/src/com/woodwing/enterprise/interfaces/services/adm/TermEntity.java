/**
 * TermEntity.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class TermEntity  implements java.io.Serializable {
    private java.math.BigInteger id;

    private java.lang.String name;

    private java.lang.String autocompleteProvider;

    private java.lang.String publishSystemId;

    public TermEntity() {
    }

    public TermEntity(
           java.math.BigInteger id,
           java.lang.String name,
           java.lang.String autocompleteProvider,
           java.lang.String publishSystemId) {
           this.id = id;
           this.name = name;
           this.autocompleteProvider = autocompleteProvider;
           this.publishSystemId = publishSystemId;
    }


    /**
     * Gets the id value for this TermEntity.
     * 
     * @return id
     */
    public java.math.BigInteger getId() {
        return id;
    }


    /**
     * Sets the id value for this TermEntity.
     * 
     * @param id
     */
    public void setId(java.math.BigInteger id) {
        this.id = id;
    }


    /**
     * Gets the name value for this TermEntity.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this TermEntity.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the autocompleteProvider value for this TermEntity.
     * 
     * @return autocompleteProvider
     */
    public java.lang.String getAutocompleteProvider() {
        return autocompleteProvider;
    }


    /**
     * Sets the autocompleteProvider value for this TermEntity.
     * 
     * @param autocompleteProvider
     */
    public void setAutocompleteProvider(java.lang.String autocompleteProvider) {
        this.autocompleteProvider = autocompleteProvider;
    }


    /**
     * Gets the publishSystemId value for this TermEntity.
     * 
     * @return publishSystemId
     */
    public java.lang.String getPublishSystemId() {
        return publishSystemId;
    }


    /**
     * Sets the publishSystemId value for this TermEntity.
     * 
     * @param publishSystemId
     */
    public void setPublishSystemId(java.lang.String publishSystemId) {
        this.publishSystemId = publishSystemId;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof TermEntity)) return false;
        TermEntity other = (TermEntity) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.id==null && other.getId()==null) || 
             (this.id!=null &&
              this.id.equals(other.getId()))) &&
            ((this.name==null && other.getName()==null) || 
             (this.name!=null &&
              this.name.equals(other.getName()))) &&
            ((this.autocompleteProvider==null && other.getAutocompleteProvider()==null) || 
             (this.autocompleteProvider!=null &&
              this.autocompleteProvider.equals(other.getAutocompleteProvider()))) &&
            ((this.publishSystemId==null && other.getPublishSystemId()==null) || 
             (this.publishSystemId!=null &&
              this.publishSystemId.equals(other.getPublishSystemId())));
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
        if (getId() != null) {
            _hashCode += getId().hashCode();
        }
        if (getName() != null) {
            _hashCode += getName().hashCode();
        }
        if (getAutocompleteProvider() != null) {
            _hashCode += getAutocompleteProvider().hashCode();
        }
        if (getPublishSystemId() != null) {
            _hashCode += getPublishSystemId().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(TermEntity.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "TermEntity"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("id");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Id"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("autocompleteProvider");
        elemField.setXmlName(new javax.xml.namespace.QName("", "AutocompleteProvider"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishSystemId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishSystemId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
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
