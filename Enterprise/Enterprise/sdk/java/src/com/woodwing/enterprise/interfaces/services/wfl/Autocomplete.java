/**
 * Autocomplete.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class Autocomplete  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String autocompleteProvider;

    private java.lang.String publishSystemId;

    private java.lang.String objectId;

    private com.woodwing.enterprise.interfaces.services.wfl.AutoSuggestProperty property;

    private java.lang.String typedValue;

    public Autocomplete() {
    }

    public Autocomplete(
           java.lang.String ticket,
           java.lang.String autocompleteProvider,
           java.lang.String publishSystemId,
           java.lang.String objectId,
           com.woodwing.enterprise.interfaces.services.wfl.AutoSuggestProperty property,
           java.lang.String typedValue) {
           this.ticket = ticket;
           this.autocompleteProvider = autocompleteProvider;
           this.publishSystemId = publishSystemId;
           this.objectId = objectId;
           this.property = property;
           this.typedValue = typedValue;
    }


    /**
     * Gets the ticket value for this Autocomplete.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this Autocomplete.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the autocompleteProvider value for this Autocomplete.
     * 
     * @return autocompleteProvider
     */
    public java.lang.String getAutocompleteProvider() {
        return autocompleteProvider;
    }


    /**
     * Sets the autocompleteProvider value for this Autocomplete.
     * 
     * @param autocompleteProvider
     */
    public void setAutocompleteProvider(java.lang.String autocompleteProvider) {
        this.autocompleteProvider = autocompleteProvider;
    }


    /**
     * Gets the publishSystemId value for this Autocomplete.
     * 
     * @return publishSystemId
     */
    public java.lang.String getPublishSystemId() {
        return publishSystemId;
    }


    /**
     * Sets the publishSystemId value for this Autocomplete.
     * 
     * @param publishSystemId
     */
    public void setPublishSystemId(java.lang.String publishSystemId) {
        this.publishSystemId = publishSystemId;
    }


    /**
     * Gets the objectId value for this Autocomplete.
     * 
     * @return objectId
     */
    public java.lang.String getObjectId() {
        return objectId;
    }


    /**
     * Sets the objectId value for this Autocomplete.
     * 
     * @param objectId
     */
    public void setObjectId(java.lang.String objectId) {
        this.objectId = objectId;
    }


    /**
     * Gets the property value for this Autocomplete.
     * 
     * @return property
     */
    public com.woodwing.enterprise.interfaces.services.wfl.AutoSuggestProperty getProperty() {
        return property;
    }


    /**
     * Sets the property value for this Autocomplete.
     * 
     * @param property
     */
    public void setProperty(com.woodwing.enterprise.interfaces.services.wfl.AutoSuggestProperty property) {
        this.property = property;
    }


    /**
     * Gets the typedValue value for this Autocomplete.
     * 
     * @return typedValue
     */
    public java.lang.String getTypedValue() {
        return typedValue;
    }


    /**
     * Sets the typedValue value for this Autocomplete.
     * 
     * @param typedValue
     */
    public void setTypedValue(java.lang.String typedValue) {
        this.typedValue = typedValue;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Autocomplete)) return false;
        Autocomplete other = (Autocomplete) obj;
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
            ((this.autocompleteProvider==null && other.getAutocompleteProvider()==null) || 
             (this.autocompleteProvider!=null &&
              this.autocompleteProvider.equals(other.getAutocompleteProvider()))) &&
            ((this.publishSystemId==null && other.getPublishSystemId()==null) || 
             (this.publishSystemId!=null &&
              this.publishSystemId.equals(other.getPublishSystemId()))) &&
            ((this.objectId==null && other.getObjectId()==null) || 
             (this.objectId!=null &&
              this.objectId.equals(other.getObjectId()))) &&
            ((this.property==null && other.getProperty()==null) || 
             (this.property!=null &&
              this.property.equals(other.getProperty()))) &&
            ((this.typedValue==null && other.getTypedValue()==null) || 
             (this.typedValue!=null &&
              this.typedValue.equals(other.getTypedValue())));
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
        if (getAutocompleteProvider() != null) {
            _hashCode += getAutocompleteProvider().hashCode();
        }
        if (getPublishSystemId() != null) {
            _hashCode += getPublishSystemId().hashCode();
        }
        if (getObjectId() != null) {
            _hashCode += getObjectId().hashCode();
        }
        if (getProperty() != null) {
            _hashCode += getProperty().hashCode();
        }
        if (getTypedValue() != null) {
            _hashCode += getTypedValue().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Autocomplete.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">Autocomplete"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("autocompleteProvider");
        elemField.setXmlName(new javax.xml.namespace.QName("", "AutocompleteProvider"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishSystemId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishSystemId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objectId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ObjectId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("property");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Property"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "AutoSuggestProperty"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("typedValue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "TypedValue"));
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
