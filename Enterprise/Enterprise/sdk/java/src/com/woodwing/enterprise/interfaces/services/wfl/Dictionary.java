/**
 * Dictionary.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class Dictionary  implements java.io.Serializable {
    private java.lang.String name;

    private java.lang.String language;

    private java.lang.String docLanguage;

    private java.lang.String wordChars;

    public Dictionary() {
    }

    public Dictionary(
           java.lang.String name,
           java.lang.String language,
           java.lang.String docLanguage,
           java.lang.String wordChars) {
           this.name = name;
           this.language = language;
           this.docLanguage = docLanguage;
           this.wordChars = wordChars;
    }


    /**
     * Gets the name value for this Dictionary.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this Dictionary.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the language value for this Dictionary.
     * 
     * @return language
     */
    public java.lang.String getLanguage() {
        return language;
    }


    /**
     * Sets the language value for this Dictionary.
     * 
     * @param language
     */
    public void setLanguage(java.lang.String language) {
        this.language = language;
    }


    /**
     * Gets the docLanguage value for this Dictionary.
     * 
     * @return docLanguage
     */
    public java.lang.String getDocLanguage() {
        return docLanguage;
    }


    /**
     * Sets the docLanguage value for this Dictionary.
     * 
     * @param docLanguage
     */
    public void setDocLanguage(java.lang.String docLanguage) {
        this.docLanguage = docLanguage;
    }


    /**
     * Gets the wordChars value for this Dictionary.
     * 
     * @return wordChars
     */
    public java.lang.String getWordChars() {
        return wordChars;
    }


    /**
     * Sets the wordChars value for this Dictionary.
     * 
     * @param wordChars
     */
    public void setWordChars(java.lang.String wordChars) {
        this.wordChars = wordChars;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Dictionary)) return false;
        Dictionary other = (Dictionary) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.name==null && other.getName()==null) || 
             (this.name!=null &&
              this.name.equals(other.getName()))) &&
            ((this.language==null && other.getLanguage()==null) || 
             (this.language!=null &&
              this.language.equals(other.getLanguage()))) &&
            ((this.docLanguage==null && other.getDocLanguage()==null) || 
             (this.docLanguage!=null &&
              this.docLanguage.equals(other.getDocLanguage()))) &&
            ((this.wordChars==null && other.getWordChars()==null) || 
             (this.wordChars!=null &&
              this.wordChars.equals(other.getWordChars())));
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
        if (getName() != null) {
            _hashCode += getName().hashCode();
        }
        if (getLanguage() != null) {
            _hashCode += getLanguage().hashCode();
        }
        if (getDocLanguage() != null) {
            _hashCode += getDocLanguage().hashCode();
        }
        if (getWordChars() != null) {
            _hashCode += getWordChars().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Dictionary.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Dictionary"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
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
        elemField.setFieldName("docLanguage");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DocLanguage"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("wordChars");
        elemField.setXmlName(new javax.xml.namespace.QName("", "WordChars"));
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
