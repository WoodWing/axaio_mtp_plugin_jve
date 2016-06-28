/**
 * PlacedQuery.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.dat;

public class PlacedQuery  implements java.io.Serializable {
    private java.lang.String queryID;

    private java.lang.String[] familyValues;

    public PlacedQuery() {
    }

    public PlacedQuery(
           java.lang.String queryID,
           java.lang.String[] familyValues) {
           this.queryID = queryID;
           this.familyValues = familyValues;
    }


    /**
     * Gets the queryID value for this PlacedQuery.
     * 
     * @return queryID
     */
    public java.lang.String getQueryID() {
        return queryID;
    }


    /**
     * Sets the queryID value for this PlacedQuery.
     * 
     * @param queryID
     */
    public void setQueryID(java.lang.String queryID) {
        this.queryID = queryID;
    }


    /**
     * Gets the familyValues value for this PlacedQuery.
     * 
     * @return familyValues
     */
    public java.lang.String[] getFamilyValues() {
        return familyValues;
    }


    /**
     * Sets the familyValues value for this PlacedQuery.
     * 
     * @param familyValues
     */
    public void setFamilyValues(java.lang.String[] familyValues) {
        this.familyValues = familyValues;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof PlacedQuery)) return false;
        PlacedQuery other = (PlacedQuery) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.queryID==null && other.getQueryID()==null) || 
             (this.queryID!=null &&
              this.queryID.equals(other.getQueryID()))) &&
            ((this.familyValues==null && other.getFamilyValues()==null) || 
             (this.familyValues!=null &&
              java.util.Arrays.equals(this.familyValues, other.getFamilyValues())));
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
        if (getQueryID() != null) {
            _hashCode += getQueryID().hashCode();
        }
        if (getFamilyValues() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getFamilyValues());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getFamilyValues(), i);
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
        new org.apache.axis.description.TypeDesc(PlacedQuery.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "PlacedQuery"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("queryID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "QueryID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("familyValues");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FamilyValues"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "String"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "FamilyValue"));
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
