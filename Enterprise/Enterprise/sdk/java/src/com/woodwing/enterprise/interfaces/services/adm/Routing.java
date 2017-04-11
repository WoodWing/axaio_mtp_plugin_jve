/**
 * Routing.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class Routing  implements java.io.Serializable {
    private java.math.BigInteger id;

    private java.math.BigInteger sectionId;

    private java.math.BigInteger statusId;

    private java.lang.String routeTo;

    public Routing() {
    }

    public Routing(
           java.math.BigInteger id,
           java.math.BigInteger sectionId,
           java.math.BigInteger statusId,
           java.lang.String routeTo) {
           this.id = id;
           this.sectionId = sectionId;
           this.statusId = statusId;
           this.routeTo = routeTo;
    }


    /**
     * Gets the id value for this Routing.
     * 
     * @return id
     */
    public java.math.BigInteger getId() {
        return id;
    }


    /**
     * Sets the id value for this Routing.
     * 
     * @param id
     */
    public void setId(java.math.BigInteger id) {
        this.id = id;
    }


    /**
     * Gets the sectionId value for this Routing.
     * 
     * @return sectionId
     */
    public java.math.BigInteger getSectionId() {
        return sectionId;
    }


    /**
     * Sets the sectionId value for this Routing.
     * 
     * @param sectionId
     */
    public void setSectionId(java.math.BigInteger sectionId) {
        this.sectionId = sectionId;
    }


    /**
     * Gets the statusId value for this Routing.
     * 
     * @return statusId
     */
    public java.math.BigInteger getStatusId() {
        return statusId;
    }


    /**
     * Sets the statusId value for this Routing.
     * 
     * @param statusId
     */
    public void setStatusId(java.math.BigInteger statusId) {
        this.statusId = statusId;
    }


    /**
     * Gets the routeTo value for this Routing.
     * 
     * @return routeTo
     */
    public java.lang.String getRouteTo() {
        return routeTo;
    }


    /**
     * Sets the routeTo value for this Routing.
     * 
     * @param routeTo
     */
    public void setRouteTo(java.lang.String routeTo) {
        this.routeTo = routeTo;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Routing)) return false;
        Routing other = (Routing) obj;
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
            ((this.sectionId==null && other.getSectionId()==null) || 
             (this.sectionId!=null &&
              this.sectionId.equals(other.getSectionId()))) &&
            ((this.statusId==null && other.getStatusId()==null) || 
             (this.statusId!=null &&
              this.statusId.equals(other.getStatusId()))) &&
            ((this.routeTo==null && other.getRouteTo()==null) || 
             (this.routeTo!=null &&
              this.routeTo.equals(other.getRouteTo())));
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
        if (getSectionId() != null) {
            _hashCode += getSectionId().hashCode();
        }
        if (getStatusId() != null) {
            _hashCode += getStatusId().hashCode();
        }
        if (getRouteTo() != null) {
            _hashCode += getRouteTo().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Routing.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Routing"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("id");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Id"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("sectionId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SectionId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("statusId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "StatusId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("routeTo");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RouteTo"));
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
