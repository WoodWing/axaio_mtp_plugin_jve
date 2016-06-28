/**
 * Attachment.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class Attachment  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.RenditionType rendition;

    private java.lang.String type;

    private byte[] content;

    private java.lang.String filePath;

    private java.lang.String fileUrl;

    private java.lang.String editionId;

    private java.lang.String contentSourceFileLink;

    public Attachment() {
    }

    public Attachment(
           com.woodwing.enterprise.interfaces.services.wfl.RenditionType rendition,
           java.lang.String type,
           byte[] content,
           java.lang.String filePath,
           java.lang.String fileUrl,
           java.lang.String editionId,
           java.lang.String contentSourceFileLink) {
           this.rendition = rendition;
           this.type = type;
           this.content = content;
           this.filePath = filePath;
           this.fileUrl = fileUrl;
           this.editionId = editionId;
           this.contentSourceFileLink = contentSourceFileLink;
    }


    /**
     * Gets the rendition value for this Attachment.
     * 
     * @return rendition
     */
    public com.woodwing.enterprise.interfaces.services.wfl.RenditionType getRendition() {
        return rendition;
    }


    /**
     * Sets the rendition value for this Attachment.
     * 
     * @param rendition
     */
    public void setRendition(com.woodwing.enterprise.interfaces.services.wfl.RenditionType rendition) {
        this.rendition = rendition;
    }


    /**
     * Gets the type value for this Attachment.
     * 
     * @return type
     */
    public java.lang.String getType() {
        return type;
    }


    /**
     * Sets the type value for this Attachment.
     * 
     * @param type
     */
    public void setType(java.lang.String type) {
        this.type = type;
    }


    /**
     * Gets the content value for this Attachment.
     * 
     * @return content
     */
    public byte[] getContent() {
        return content;
    }


    /**
     * Sets the content value for this Attachment.
     * 
     * @param content
     */
    public void setContent(byte[] content) {
        this.content = content;
    }


    /**
     * Gets the filePath value for this Attachment.
     * 
     * @return filePath
     */
    public java.lang.String getFilePath() {
        return filePath;
    }


    /**
     * Sets the filePath value for this Attachment.
     * 
     * @param filePath
     */
    public void setFilePath(java.lang.String filePath) {
        this.filePath = filePath;
    }


    /**
     * Gets the fileUrl value for this Attachment.
     * 
     * @return fileUrl
     */
    public java.lang.String getFileUrl() {
        return fileUrl;
    }


    /**
     * Sets the fileUrl value for this Attachment.
     * 
     * @param fileUrl
     */
    public void setFileUrl(java.lang.String fileUrl) {
        this.fileUrl = fileUrl;
    }


    /**
     * Gets the editionId value for this Attachment.
     * 
     * @return editionId
     */
    public java.lang.String getEditionId() {
        return editionId;
    }


    /**
     * Sets the editionId value for this Attachment.
     * 
     * @param editionId
     */
    public void setEditionId(java.lang.String editionId) {
        this.editionId = editionId;
    }


    /**
     * Gets the contentSourceFileLink value for this Attachment.
     * 
     * @return contentSourceFileLink
     */
    public java.lang.String getContentSourceFileLink() {
        return contentSourceFileLink;
    }


    /**
     * Sets the contentSourceFileLink value for this Attachment.
     * 
     * @param contentSourceFileLink
     */
    public void setContentSourceFileLink(java.lang.String contentSourceFileLink) {
        this.contentSourceFileLink = contentSourceFileLink;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Attachment)) return false;
        Attachment other = (Attachment) obj;
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
            ((this.type==null && other.getType()==null) || 
             (this.type!=null &&
              this.type.equals(other.getType()))) &&
            ((this.content==null && other.getContent()==null) || 
             (this.content!=null &&
              java.util.Arrays.equals(this.content, other.getContent()))) &&
            ((this.filePath==null && other.getFilePath()==null) || 
             (this.filePath!=null &&
              this.filePath.equals(other.getFilePath()))) &&
            ((this.fileUrl==null && other.getFileUrl()==null) || 
             (this.fileUrl!=null &&
              this.fileUrl.equals(other.getFileUrl()))) &&
            ((this.editionId==null && other.getEditionId()==null) || 
             (this.editionId!=null &&
              this.editionId.equals(other.getEditionId()))) &&
            ((this.contentSourceFileLink==null && other.getContentSourceFileLink()==null) || 
             (this.contentSourceFileLink!=null &&
              this.contentSourceFileLink.equals(other.getContentSourceFileLink())));
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
        if (getType() != null) {
            _hashCode += getType().hashCode();
        }
        if (getContent() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getContent());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getContent(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getFilePath() != null) {
            _hashCode += getFilePath().hashCode();
        }
        if (getFileUrl() != null) {
            _hashCode += getFileUrl().hashCode();
        }
        if (getEditionId() != null) {
            _hashCode += getEditionId().hashCode();
        }
        if (getContentSourceFileLink() != null) {
            _hashCode += getContentSourceFileLink().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Attachment.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Attachment"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("rendition");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Rendition"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "RenditionType"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("type");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Type"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("content");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Content"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://schemas.xmlsoap.org/soap/encoding/", "base64Binary"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("filePath");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FilePath"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("fileUrl");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FileUrl"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("editionId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "EditionId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("contentSourceFileLink");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ContentSourceFileLink"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
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
