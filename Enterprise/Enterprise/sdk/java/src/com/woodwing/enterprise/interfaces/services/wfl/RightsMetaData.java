/**
 * RightsMetaData.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class RightsMetaData  implements java.io.Serializable {
    private java.lang.Boolean copyrightMarked;

    private java.lang.String copyright;

    private java.lang.String copyrightURL;

    public RightsMetaData() {
    }

    public RightsMetaData(
           java.lang.Boolean copyrightMarked,
           java.lang.String copyright,
           java.lang.String copyrightURL) {
           this.copyrightMarked = copyrightMarked;
           this.copyright = copyright;
           this.copyrightURL = copyrightURL;
    }


    /**
     * Gets the copyrightMarked value for this RightsMetaData.
     * 
     * @return copyrightMarked
     */
    public java.lang.Boolean getCopyrightMarked() {
        return copyrightMarked;
    }


    /**
     * Sets the copyrightMarked value for this RightsMetaData.
     * 
     * @param copyrightMarked
     */
    public void setCopyrightMarked(java.lang.Boolean copyrightMarked) {
        this.copyrightMarked = copyrightMarked;
    }


    /**
     * Gets the copyright value for this RightsMetaData.
     * 
     * @return copyright
     */
    public java.lang.String getCopyright() {
        return copyright;
    }


    /**
     * Sets the copyright value for this RightsMetaData.
     * 
     * @param copyright
     */
    public void setCopyright(java.lang.String copyright) {
        this.copyright = copyright;
    }


    /**
     * Gets the copyrightURL value for this RightsMetaData.
     * 
     * @return copyrightURL
     */
    public java.lang.String getCopyrightURL() {
        return copyrightURL;
    }


    /**
     * Sets the copyrightURL value for this RightsMetaData.
     * 
     * @param copyrightURL
     */
    public void setCopyrightURL(java.lang.String copyrightURL) {
        this.copyrightURL = copyrightURL;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof RightsMetaData)) return false;
        RightsMetaData other = (RightsMetaData) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.copyrightMarked==null && other.getCopyrightMarked()==null) || 
             (this.copyrightMarked!=null &&
              this.copyrightMarked.equals(other.getCopyrightMarked()))) &&
            ((this.copyright==null && other.getCopyright()==null) || 
             (this.copyright!=null &&
              this.copyright.equals(other.getCopyright()))) &&
            ((this.copyrightURL==null && other.getCopyrightURL()==null) || 
             (this.copyrightURL!=null &&
              this.copyrightURL.equals(other.getCopyrightURL())));
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
        if (getCopyrightMarked() != null) {
            _hashCode += getCopyrightMarked().hashCode();
        }
        if (getCopyright() != null) {
            _hashCode += getCopyright().hashCode();
        }
        if (getCopyrightURL() != null) {
            _hashCode += getCopyrightURL().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(RightsMetaData.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "RightsMetaData"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("copyrightMarked");
        elemField.setXmlName(new javax.xml.namespace.QName("", "CopyrightMarked"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("copyright");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Copyright"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("copyrightURL");
        elemField.setXmlName(new javax.xml.namespace.QName("", "CopyrightURL"));
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
