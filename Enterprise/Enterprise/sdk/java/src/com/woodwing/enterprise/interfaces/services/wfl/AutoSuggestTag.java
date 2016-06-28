/**
 * AutoSuggestTag.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class AutoSuggestTag  implements java.io.Serializable {
    private java.lang.String value;

    private double score;

    private java.math.BigInteger startPos;

    private java.math.BigInteger length;

    public AutoSuggestTag() {
    }

    public AutoSuggestTag(
           java.lang.String value,
           double score,
           java.math.BigInteger startPos,
           java.math.BigInteger length) {
           this.value = value;
           this.score = score;
           this.startPos = startPos;
           this.length = length;
    }


    /**
     * Gets the value value for this AutoSuggestTag.
     * 
     * @return value
     */
    public java.lang.String getValue() {
        return value;
    }


    /**
     * Sets the value value for this AutoSuggestTag.
     * 
     * @param value
     */
    public void setValue(java.lang.String value) {
        this.value = value;
    }


    /**
     * Gets the score value for this AutoSuggestTag.
     * 
     * @return score
     */
    public double getScore() {
        return score;
    }


    /**
     * Sets the score value for this AutoSuggestTag.
     * 
     * @param score
     */
    public void setScore(double score) {
        this.score = score;
    }


    /**
     * Gets the startPos value for this AutoSuggestTag.
     * 
     * @return startPos
     */
    public java.math.BigInteger getStartPos() {
        return startPos;
    }


    /**
     * Sets the startPos value for this AutoSuggestTag.
     * 
     * @param startPos
     */
    public void setStartPos(java.math.BigInteger startPos) {
        this.startPos = startPos;
    }


    /**
     * Gets the length value for this AutoSuggestTag.
     * 
     * @return length
     */
    public java.math.BigInteger getLength() {
        return length;
    }


    /**
     * Sets the length value for this AutoSuggestTag.
     * 
     * @param length
     */
    public void setLength(java.math.BigInteger length) {
        this.length = length;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof AutoSuggestTag)) return false;
        AutoSuggestTag other = (AutoSuggestTag) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.value==null && other.getValue()==null) || 
             (this.value!=null &&
              this.value.equals(other.getValue()))) &&
            this.score == other.getScore() &&
            ((this.startPos==null && other.getStartPos()==null) || 
             (this.startPos!=null &&
              this.startPos.equals(other.getStartPos()))) &&
            ((this.length==null && other.getLength()==null) || 
             (this.length!=null &&
              this.length.equals(other.getLength())));
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
        if (getValue() != null) {
            _hashCode += getValue().hashCode();
        }
        _hashCode += new Double(getScore()).hashCode();
        if (getStartPos() != null) {
            _hashCode += getStartPos().hashCode();
        }
        if (getLength() != null) {
            _hashCode += getLength().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(AutoSuggestTag.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "AutoSuggestTag"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("value");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Value"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("score");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Score"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("startPos");
        elemField.setXmlName(new javax.xml.namespace.QName("", "StartPos"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("length");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Length"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
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
