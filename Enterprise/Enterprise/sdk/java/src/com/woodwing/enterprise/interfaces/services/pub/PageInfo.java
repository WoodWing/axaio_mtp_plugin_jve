/**
 * PageInfo.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public class PageInfo  implements java.io.Serializable {
    private java.lang.String pageNumber;

    private java.lang.String pageSequence;

    private java.lang.String pageOrder;

    public PageInfo() {
    }

    public PageInfo(
           java.lang.String pageNumber,
           java.lang.String pageSequence,
           java.lang.String pageOrder) {
           this.pageNumber = pageNumber;
           this.pageSequence = pageSequence;
           this.pageOrder = pageOrder;
    }


    /**
     * Gets the pageNumber value for this PageInfo.
     * 
     * @return pageNumber
     */
    public java.lang.String getPageNumber() {
        return pageNumber;
    }


    /**
     * Sets the pageNumber value for this PageInfo.
     * 
     * @param pageNumber
     */
    public void setPageNumber(java.lang.String pageNumber) {
        this.pageNumber = pageNumber;
    }


    /**
     * Gets the pageSequence value for this PageInfo.
     * 
     * @return pageSequence
     */
    public java.lang.String getPageSequence() {
        return pageSequence;
    }


    /**
     * Sets the pageSequence value for this PageInfo.
     * 
     * @param pageSequence
     */
    public void setPageSequence(java.lang.String pageSequence) {
        this.pageSequence = pageSequence;
    }


    /**
     * Gets the pageOrder value for this PageInfo.
     * 
     * @return pageOrder
     */
    public java.lang.String getPageOrder() {
        return pageOrder;
    }


    /**
     * Sets the pageOrder value for this PageInfo.
     * 
     * @param pageOrder
     */
    public void setPageOrder(java.lang.String pageOrder) {
        this.pageOrder = pageOrder;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof PageInfo)) return false;
        PageInfo other = (PageInfo) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.pageNumber==null && other.getPageNumber()==null) || 
             (this.pageNumber!=null &&
              this.pageNumber.equals(other.getPageNumber()))) &&
            ((this.pageSequence==null && other.getPageSequence()==null) || 
             (this.pageSequence!=null &&
              this.pageSequence.equals(other.getPageSequence()))) &&
            ((this.pageOrder==null && other.getPageOrder()==null) || 
             (this.pageOrder!=null &&
              this.pageOrder.equals(other.getPageOrder())));
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
        if (getPageNumber() != null) {
            _hashCode += getPageNumber().hashCode();
        }
        if (getPageSequence() != null) {
            _hashCode += getPageSequence().hashCode();
        }
        if (getPageOrder() != null) {
            _hashCode += getPageOrder().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(PageInfo.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PageInfo"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageNumber");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageNumber"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageSequence");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageSequence"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageOrder");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageOrder"));
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
