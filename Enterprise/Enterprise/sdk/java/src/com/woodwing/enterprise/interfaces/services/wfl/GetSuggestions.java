/**
 * GetSuggestions.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class GetSuggestions  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String language;

    private java.lang.String publicationId;

    private java.lang.String[] wordsToCheck;

    public GetSuggestions() {
    }

    public GetSuggestions(
           java.lang.String ticket,
           java.lang.String language,
           java.lang.String publicationId,
           java.lang.String[] wordsToCheck) {
           this.ticket = ticket;
           this.language = language;
           this.publicationId = publicationId;
           this.wordsToCheck = wordsToCheck;
    }


    /**
     * Gets the ticket value for this GetSuggestions.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this GetSuggestions.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the language value for this GetSuggestions.
     * 
     * @return language
     */
    public java.lang.String getLanguage() {
        return language;
    }


    /**
     * Sets the language value for this GetSuggestions.
     * 
     * @param language
     */
    public void setLanguage(java.lang.String language) {
        this.language = language;
    }


    /**
     * Gets the publicationId value for this GetSuggestions.
     * 
     * @return publicationId
     */
    public java.lang.String getPublicationId() {
        return publicationId;
    }


    /**
     * Sets the publicationId value for this GetSuggestions.
     * 
     * @param publicationId
     */
    public void setPublicationId(java.lang.String publicationId) {
        this.publicationId = publicationId;
    }


    /**
     * Gets the wordsToCheck value for this GetSuggestions.
     * 
     * @return wordsToCheck
     */
    public java.lang.String[] getWordsToCheck() {
        return wordsToCheck;
    }


    /**
     * Sets the wordsToCheck value for this GetSuggestions.
     * 
     * @param wordsToCheck
     */
    public void setWordsToCheck(java.lang.String[] wordsToCheck) {
        this.wordsToCheck = wordsToCheck;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetSuggestions)) return false;
        GetSuggestions other = (GetSuggestions) obj;
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
            ((this.language==null && other.getLanguage()==null) || 
             (this.language!=null &&
              this.language.equals(other.getLanguage()))) &&
            ((this.publicationId==null && other.getPublicationId()==null) || 
             (this.publicationId!=null &&
              this.publicationId.equals(other.getPublicationId()))) &&
            ((this.wordsToCheck==null && other.getWordsToCheck()==null) || 
             (this.wordsToCheck!=null &&
              java.util.Arrays.equals(this.wordsToCheck, other.getWordsToCheck())));
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
        if (getLanguage() != null) {
            _hashCode += getLanguage().hashCode();
        }
        if (getPublicationId() != null) {
            _hashCode += getPublicationId().hashCode();
        }
        if (getWordsToCheck() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getWordsToCheck());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getWordsToCheck(), i);
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
        new org.apache.axis.description.TypeDesc(GetSuggestions.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetSuggestions"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("language");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Language"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publicationId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublicationId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("wordsToCheck");
        elemField.setXmlName(new javax.xml.namespace.QName("", "WordsToCheck"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
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
