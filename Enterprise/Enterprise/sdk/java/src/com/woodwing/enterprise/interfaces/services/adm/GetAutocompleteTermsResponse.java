/**
 * GetAutocompleteTermsResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class GetAutocompleteTermsResponse  implements java.io.Serializable {
    private java.lang.String[] terms;

    private java.math.BigInteger firstEntry;

    private java.math.BigInteger listedEntries;

    private java.math.BigInteger totalEntries;

    public GetAutocompleteTermsResponse() {
    }

    public GetAutocompleteTermsResponse(
           java.lang.String[] terms,
           java.math.BigInteger firstEntry,
           java.math.BigInteger listedEntries,
           java.math.BigInteger totalEntries) {
           this.terms = terms;
           this.firstEntry = firstEntry;
           this.listedEntries = listedEntries;
           this.totalEntries = totalEntries;
    }


    /**
     * Gets the terms value for this GetAutocompleteTermsResponse.
     * 
     * @return terms
     */
    public java.lang.String[] getTerms() {
        return terms;
    }


    /**
     * Sets the terms value for this GetAutocompleteTermsResponse.
     * 
     * @param terms
     */
    public void setTerms(java.lang.String[] terms) {
        this.terms = terms;
    }


    /**
     * Gets the firstEntry value for this GetAutocompleteTermsResponse.
     * 
     * @return firstEntry
     */
    public java.math.BigInteger getFirstEntry() {
        return firstEntry;
    }


    /**
     * Sets the firstEntry value for this GetAutocompleteTermsResponse.
     * 
     * @param firstEntry
     */
    public void setFirstEntry(java.math.BigInteger firstEntry) {
        this.firstEntry = firstEntry;
    }


    /**
     * Gets the listedEntries value for this GetAutocompleteTermsResponse.
     * 
     * @return listedEntries
     */
    public java.math.BigInteger getListedEntries() {
        return listedEntries;
    }


    /**
     * Sets the listedEntries value for this GetAutocompleteTermsResponse.
     * 
     * @param listedEntries
     */
    public void setListedEntries(java.math.BigInteger listedEntries) {
        this.listedEntries = listedEntries;
    }


    /**
     * Gets the totalEntries value for this GetAutocompleteTermsResponse.
     * 
     * @return totalEntries
     */
    public java.math.BigInteger getTotalEntries() {
        return totalEntries;
    }


    /**
     * Sets the totalEntries value for this GetAutocompleteTermsResponse.
     * 
     * @param totalEntries
     */
    public void setTotalEntries(java.math.BigInteger totalEntries) {
        this.totalEntries = totalEntries;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetAutocompleteTermsResponse)) return false;
        GetAutocompleteTermsResponse other = (GetAutocompleteTermsResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.terms==null && other.getTerms()==null) || 
             (this.terms!=null &&
              java.util.Arrays.equals(this.terms, other.getTerms()))) &&
            ((this.firstEntry==null && other.getFirstEntry()==null) || 
             (this.firstEntry!=null &&
              this.firstEntry.equals(other.getFirstEntry()))) &&
            ((this.listedEntries==null && other.getListedEntries()==null) || 
             (this.listedEntries!=null &&
              this.listedEntries.equals(other.getListedEntries()))) &&
            ((this.totalEntries==null && other.getTotalEntries()==null) || 
             (this.totalEntries!=null &&
              this.totalEntries.equals(other.getTotalEntries())));
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
        if (getTerms() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getTerms());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getTerms(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getFirstEntry() != null) {
            _hashCode += getFirstEntry().hashCode();
        }
        if (getListedEntries() != null) {
            _hashCode += getListedEntries().hashCode();
        }
        if (getTotalEntries() != null) {
            _hashCode += getTotalEntries().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetAutocompleteTermsResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetAutocompleteTermsResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("terms");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Terms"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "String"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("firstEntry");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FirstEntry"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("listedEntries");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ListedEntries"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("totalEntries");
        elemField.setXmlName(new javax.xml.namespace.QName("", "TotalEntries"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
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
