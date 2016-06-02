/**
 * PublishHistory.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public class PublishHistory  implements java.io.Serializable {
    private java.util.Calendar publishedDate;

    private java.util.Calendar sendDate;

    private java.lang.String publishedBy;

    private com.woodwing.enterprise.interfaces.services.pub.PublishedObject[] publishedObjects;

    public PublishHistory() {
    }

    public PublishHistory(
           java.util.Calendar publishedDate,
           java.util.Calendar sendDate,
           java.lang.String publishedBy,
           com.woodwing.enterprise.interfaces.services.pub.PublishedObject[] publishedObjects) {
           this.publishedDate = publishedDate;
           this.sendDate = sendDate;
           this.publishedBy = publishedBy;
           this.publishedObjects = publishedObjects;
    }


    /**
     * Gets the publishedDate value for this PublishHistory.
     * 
     * @return publishedDate
     */
    public java.util.Calendar getPublishedDate() {
        return publishedDate;
    }


    /**
     * Sets the publishedDate value for this PublishHistory.
     * 
     * @param publishedDate
     */
    public void setPublishedDate(java.util.Calendar publishedDate) {
        this.publishedDate = publishedDate;
    }


    /**
     * Gets the sendDate value for this PublishHistory.
     * 
     * @return sendDate
     */
    public java.util.Calendar getSendDate() {
        return sendDate;
    }


    /**
     * Sets the sendDate value for this PublishHistory.
     * 
     * @param sendDate
     */
    public void setSendDate(java.util.Calendar sendDate) {
        this.sendDate = sendDate;
    }


    /**
     * Gets the publishedBy value for this PublishHistory.
     * 
     * @return publishedBy
     */
    public java.lang.String getPublishedBy() {
        return publishedBy;
    }


    /**
     * Sets the publishedBy value for this PublishHistory.
     * 
     * @param publishedBy
     */
    public void setPublishedBy(java.lang.String publishedBy) {
        this.publishedBy = publishedBy;
    }


    /**
     * Gets the publishedObjects value for this PublishHistory.
     * 
     * @return publishedObjects
     */
    public com.woodwing.enterprise.interfaces.services.pub.PublishedObject[] getPublishedObjects() {
        return publishedObjects;
    }


    /**
     * Sets the publishedObjects value for this PublishHistory.
     * 
     * @param publishedObjects
     */
    public void setPublishedObjects(com.woodwing.enterprise.interfaces.services.pub.PublishedObject[] publishedObjects) {
        this.publishedObjects = publishedObjects;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof PublishHistory)) return false;
        PublishHistory other = (PublishHistory) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.publishedDate==null && other.getPublishedDate()==null) || 
             (this.publishedDate!=null &&
              this.publishedDate.equals(other.getPublishedDate()))) &&
            ((this.sendDate==null && other.getSendDate()==null) || 
             (this.sendDate!=null &&
              this.sendDate.equals(other.getSendDate()))) &&
            ((this.publishedBy==null && other.getPublishedBy()==null) || 
             (this.publishedBy!=null &&
              this.publishedBy.equals(other.getPublishedBy()))) &&
            ((this.publishedObjects==null && other.getPublishedObjects()==null) || 
             (this.publishedObjects!=null &&
              java.util.Arrays.equals(this.publishedObjects, other.getPublishedObjects())));
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
        if (getPublishedDate() != null) {
            _hashCode += getPublishedDate().hashCode();
        }
        if (getSendDate() != null) {
            _hashCode += getSendDate().hashCode();
        }
        if (getPublishedBy() != null) {
            _hashCode += getPublishedBy().hashCode();
        }
        if (getPublishedObjects() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPublishedObjects());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPublishedObjects(), i);
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
        new org.apache.axis.description.TypeDesc(PublishHistory.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishHistory"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishedDate");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishedDate"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "dateTime"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("sendDate");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SendDate"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "dateTime"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishedBy");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishedBy"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishedObjects");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishedObjects"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishedObject"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "PublishedObject"));
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
