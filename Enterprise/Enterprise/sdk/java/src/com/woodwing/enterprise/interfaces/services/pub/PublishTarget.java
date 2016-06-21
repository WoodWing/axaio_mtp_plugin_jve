/**
 * PublishTarget.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public class PublishTarget  implements java.io.Serializable {
    private java.lang.String pubChannelID;

    private java.lang.String issueID;

    private java.lang.String editionID;

    private java.util.Calendar publishedDate;

    public PublishTarget() {
    }

    public PublishTarget(
           java.lang.String pubChannelID,
           java.lang.String issueID,
           java.lang.String editionID,
           java.util.Calendar publishedDate) {
           this.pubChannelID = pubChannelID;
           this.issueID = issueID;
           this.editionID = editionID;
           this.publishedDate = publishedDate;
    }


    /**
     * Gets the pubChannelID value for this PublishTarget.
     * 
     * @return pubChannelID
     */
    public java.lang.String getPubChannelID() {
        return pubChannelID;
    }


    /**
     * Sets the pubChannelID value for this PublishTarget.
     * 
     * @param pubChannelID
     */
    public void setPubChannelID(java.lang.String pubChannelID) {
        this.pubChannelID = pubChannelID;
    }


    /**
     * Gets the issueID value for this PublishTarget.
     * 
     * @return issueID
     */
    public java.lang.String getIssueID() {
        return issueID;
    }


    /**
     * Sets the issueID value for this PublishTarget.
     * 
     * @param issueID
     */
    public void setIssueID(java.lang.String issueID) {
        this.issueID = issueID;
    }


    /**
     * Gets the editionID value for this PublishTarget.
     * 
     * @return editionID
     */
    public java.lang.String getEditionID() {
        return editionID;
    }


    /**
     * Sets the editionID value for this PublishTarget.
     * 
     * @param editionID
     */
    public void setEditionID(java.lang.String editionID) {
        this.editionID = editionID;
    }


    /**
     * Gets the publishedDate value for this PublishTarget.
     * 
     * @return publishedDate
     */
    public java.util.Calendar getPublishedDate() {
        return publishedDate;
    }


    /**
     * Sets the publishedDate value for this PublishTarget.
     * 
     * @param publishedDate
     */
    public void setPublishedDate(java.util.Calendar publishedDate) {
        this.publishedDate = publishedDate;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof PublishTarget)) return false;
        PublishTarget other = (PublishTarget) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.pubChannelID==null && other.getPubChannelID()==null) || 
             (this.pubChannelID!=null &&
              this.pubChannelID.equals(other.getPubChannelID()))) &&
            ((this.issueID==null && other.getIssueID()==null) || 
             (this.issueID!=null &&
              this.issueID.equals(other.getIssueID()))) &&
            ((this.editionID==null && other.getEditionID()==null) || 
             (this.editionID!=null &&
              this.editionID.equals(other.getEditionID()))) &&
            ((this.publishedDate==null && other.getPublishedDate()==null) || 
             (this.publishedDate!=null &&
              this.publishedDate.equals(other.getPublishedDate())));
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
        if (getPubChannelID() != null) {
            _hashCode += getPubChannelID().hashCode();
        }
        if (getIssueID() != null) {
            _hashCode += getIssueID().hashCode();
        }
        if (getEditionID() != null) {
            _hashCode += getEditionID().hashCode();
        }
        if (getPublishedDate() != null) {
            _hashCode += getPublishedDate().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(PublishTarget.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishTarget"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pubChannelID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PubChannelID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("issueID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "IssueID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("editionID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "EditionID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishedDate");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishedDate"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "dateTime"));
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
