/**
 * Rendition.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class Rendition  implements java.io.Serializable {
    private java.lang.String rendition;

    private java.lang.String fileSize;

    private java.lang.String format;

    private com.woodwing.enterprise.interfaces.services.wfl.Attachment attachment;

    public Rendition() {
    }

    public Rendition(
           java.lang.String rendition,
           java.lang.String fileSize,
           java.lang.String format,
           com.woodwing.enterprise.interfaces.services.wfl.Attachment attachment) {
           this.rendition = rendition;
           this.fileSize = fileSize;
           this.format = format;
           this.attachment = attachment;
    }


    /**
     * Gets the rendition value for this Rendition.
     * 
     * @return rendition
     */
    public java.lang.String getRendition() {
        return rendition;
    }


    /**
     * Sets the rendition value for this Rendition.
     * 
     * @param rendition
     */
    public void setRendition(java.lang.String rendition) {
        this.rendition = rendition;
    }


    /**
     * Gets the fileSize value for this Rendition.
     * 
     * @return fileSize
     */
    public java.lang.String getFileSize() {
        return fileSize;
    }


    /**
     * Sets the fileSize value for this Rendition.
     * 
     * @param fileSize
     */
    public void setFileSize(java.lang.String fileSize) {
        this.fileSize = fileSize;
    }


    /**
     * Gets the format value for this Rendition.
     * 
     * @return format
     */
    public java.lang.String getFormat() {
        return format;
    }


    /**
     * Sets the format value for this Rendition.
     * 
     * @param format
     */
    public void setFormat(java.lang.String format) {
        this.format = format;
    }


    /**
     * Gets the attachment value for this Rendition.
     * 
     * @return attachment
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Attachment getAttachment() {
        return attachment;
    }


    /**
     * Sets the attachment value for this Rendition.
     * 
     * @param attachment
     */
    public void setAttachment(com.woodwing.enterprise.interfaces.services.wfl.Attachment attachment) {
        this.attachment = attachment;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Rendition)) return false;
        Rendition other = (Rendition) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.rendition==null && other.getRendition()==null) || 
             (this.rendition!=null &&
              this.rendition.equals(other.getRendition()))) &&
            ((this.fileSize==null && other.getFileSize()==null) || 
             (this.fileSize!=null &&
              this.fileSize.equals(other.getFileSize()))) &&
            ((this.format==null && other.getFormat()==null) || 
             (this.format!=null &&
              this.format.equals(other.getFormat()))) &&
            ((this.attachment==null && other.getAttachment()==null) || 
             (this.attachment!=null &&
              this.attachment.equals(other.getAttachment())));
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
        if (getRendition() != null) {
            _hashCode += getRendition().hashCode();
        }
        if (getFileSize() != null) {
            _hashCode += getFileSize().hashCode();
        }
        if (getFormat() != null) {
            _hashCode += getFormat().hashCode();
        }
        if (getAttachment() != null) {
            _hashCode += getAttachment().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Rendition.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Rendition"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("rendition");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Rendition"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("fileSize");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FileSize"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("format");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Format"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("attachment");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Attachment"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Attachment"));
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
