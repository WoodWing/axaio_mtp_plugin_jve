/**
 * Suggestions.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class Suggestions  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String suggestionProvider;

    private java.lang.String objectId;

    private com.woodwing.enterprise.interfaces.services.wfl.MetaDataValue[] metaData;

    private com.woodwing.enterprise.interfaces.services.wfl.AutoSuggestProperty[] suggestForProperties;

    public Suggestions() {
    }

    public Suggestions(
           java.lang.String ticket,
           java.lang.String suggestionProvider,
           java.lang.String objectId,
           com.woodwing.enterprise.interfaces.services.wfl.MetaDataValue[] metaData,
           com.woodwing.enterprise.interfaces.services.wfl.AutoSuggestProperty[] suggestForProperties) {
           this.ticket = ticket;
           this.suggestionProvider = suggestionProvider;
           this.objectId = objectId;
           this.metaData = metaData;
           this.suggestForProperties = suggestForProperties;
    }


    /**
     * Gets the ticket value for this Suggestions.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this Suggestions.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the suggestionProvider value for this Suggestions.
     * 
     * @return suggestionProvider
     */
    public java.lang.String getSuggestionProvider() {
        return suggestionProvider;
    }


    /**
     * Sets the suggestionProvider value for this Suggestions.
     * 
     * @param suggestionProvider
     */
    public void setSuggestionProvider(java.lang.String suggestionProvider) {
        this.suggestionProvider = suggestionProvider;
    }


    /**
     * Gets the objectId value for this Suggestions.
     * 
     * @return objectId
     */
    public java.lang.String getObjectId() {
        return objectId;
    }


    /**
     * Sets the objectId value for this Suggestions.
     * 
     * @param objectId
     */
    public void setObjectId(java.lang.String objectId) {
        this.objectId = objectId;
    }


    /**
     * Gets the metaData value for this Suggestions.
     * 
     * @return metaData
     */
    public com.woodwing.enterprise.interfaces.services.wfl.MetaDataValue[] getMetaData() {
        return metaData;
    }


    /**
     * Sets the metaData value for this Suggestions.
     * 
     * @param metaData
     */
    public void setMetaData(com.woodwing.enterprise.interfaces.services.wfl.MetaDataValue[] metaData) {
        this.metaData = metaData;
    }


    /**
     * Gets the suggestForProperties value for this Suggestions.
     * 
     * @return suggestForProperties
     */
    public com.woodwing.enterprise.interfaces.services.wfl.AutoSuggestProperty[] getSuggestForProperties() {
        return suggestForProperties;
    }


    /**
     * Sets the suggestForProperties value for this Suggestions.
     * 
     * @param suggestForProperties
     */
    public void setSuggestForProperties(com.woodwing.enterprise.interfaces.services.wfl.AutoSuggestProperty[] suggestForProperties) {
        this.suggestForProperties = suggestForProperties;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Suggestions)) return false;
        Suggestions other = (Suggestions) obj;
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
            ((this.suggestionProvider==null && other.getSuggestionProvider()==null) || 
             (this.suggestionProvider!=null &&
              this.suggestionProvider.equals(other.getSuggestionProvider()))) &&
            ((this.objectId==null && other.getObjectId()==null) || 
             (this.objectId!=null &&
              this.objectId.equals(other.getObjectId()))) &&
            ((this.metaData==null && other.getMetaData()==null) || 
             (this.metaData!=null &&
              java.util.Arrays.equals(this.metaData, other.getMetaData()))) &&
            ((this.suggestForProperties==null && other.getSuggestForProperties()==null) || 
             (this.suggestForProperties!=null &&
              java.util.Arrays.equals(this.suggestForProperties, other.getSuggestForProperties())));
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
        if (getSuggestionProvider() != null) {
            _hashCode += getSuggestionProvider().hashCode();
        }
        if (getObjectId() != null) {
            _hashCode += getObjectId().hashCode();
        }
        if (getMetaData() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getMetaData());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getMetaData(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getSuggestForProperties() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getSuggestForProperties());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getSuggestForProperties(), i);
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
        new org.apache.axis.description.TypeDesc(Suggestions.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">Suggestions"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("suggestionProvider");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SuggestionProvider"));
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
        elemField.setFieldName("metaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MetaDataValue"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "MetaDataValue"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("suggestForProperties");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SuggestForProperties"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "AutoSuggestProperty"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "AutoSuggestProperty"));
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
