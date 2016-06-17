/**
 * PublishedDossier.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public class PublishedDossier  implements java.io.Serializable {
    private java.lang.String dossierID;

    private com.woodwing.enterprise.interfaces.services.pub.PublishTarget target;

    private java.util.Calendar publishedDate;

    private com.woodwing.enterprise.interfaces.services.pub.UserMessage publishMessage;

    private java.lang.Boolean online;

    private java.lang.String URL;

    private com.woodwing.enterprise.interfaces.services.pub.Field[] fields;

    private com.woodwing.enterprise.interfaces.services.pub.PublishHistory[] history;

    public PublishedDossier() {
    }

    public PublishedDossier(
           java.lang.String dossierID,
           com.woodwing.enterprise.interfaces.services.pub.PublishTarget target,
           java.util.Calendar publishedDate,
           com.woodwing.enterprise.interfaces.services.pub.UserMessage publishMessage,
           java.lang.Boolean online,
           java.lang.String URL,
           com.woodwing.enterprise.interfaces.services.pub.Field[] fields,
           com.woodwing.enterprise.interfaces.services.pub.PublishHistory[] history) {
           this.dossierID = dossierID;
           this.target = target;
           this.publishedDate = publishedDate;
           this.publishMessage = publishMessage;
           this.online = online;
           this.URL = URL;
           this.fields = fields;
           this.history = history;
    }


    /**
     * Gets the dossierID value for this PublishedDossier.
     * 
     * @return dossierID
     */
    public java.lang.String getDossierID() {
        return dossierID;
    }


    /**
     * Sets the dossierID value for this PublishedDossier.
     * 
     * @param dossierID
     */
    public void setDossierID(java.lang.String dossierID) {
        this.dossierID = dossierID;
    }


    /**
     * Gets the target value for this PublishedDossier.
     * 
     * @return target
     */
    public com.woodwing.enterprise.interfaces.services.pub.PublishTarget getTarget() {
        return target;
    }


    /**
     * Sets the target value for this PublishedDossier.
     * 
     * @param target
     */
    public void setTarget(com.woodwing.enterprise.interfaces.services.pub.PublishTarget target) {
        this.target = target;
    }


    /**
     * Gets the publishedDate value for this PublishedDossier.
     * 
     * @return publishedDate
     */
    public java.util.Calendar getPublishedDate() {
        return publishedDate;
    }


    /**
     * Sets the publishedDate value for this PublishedDossier.
     * 
     * @param publishedDate
     */
    public void setPublishedDate(java.util.Calendar publishedDate) {
        this.publishedDate = publishedDate;
    }


    /**
     * Gets the publishMessage value for this PublishedDossier.
     * 
     * @return publishMessage
     */
    public com.woodwing.enterprise.interfaces.services.pub.UserMessage getPublishMessage() {
        return publishMessage;
    }


    /**
     * Sets the publishMessage value for this PublishedDossier.
     * 
     * @param publishMessage
     */
    public void setPublishMessage(com.woodwing.enterprise.interfaces.services.pub.UserMessage publishMessage) {
        this.publishMessage = publishMessage;
    }


    /**
     * Gets the online value for this PublishedDossier.
     * 
     * @return online
     */
    public java.lang.Boolean getOnline() {
        return online;
    }


    /**
     * Sets the online value for this PublishedDossier.
     * 
     * @param online
     */
    public void setOnline(java.lang.Boolean online) {
        this.online = online;
    }


    /**
     * Gets the URL value for this PublishedDossier.
     * 
     * @return URL
     */
    public java.lang.String getURL() {
        return URL;
    }


    /**
     * Sets the URL value for this PublishedDossier.
     * 
     * @param URL
     */
    public void setURL(java.lang.String URL) {
        this.URL = URL;
    }


    /**
     * Gets the fields value for this PublishedDossier.
     * 
     * @return fields
     */
    public com.woodwing.enterprise.interfaces.services.pub.Field[] getFields() {
        return fields;
    }


    /**
     * Sets the fields value for this PublishedDossier.
     * 
     * @param fields
     */
    public void setFields(com.woodwing.enterprise.interfaces.services.pub.Field[] fields) {
        this.fields = fields;
    }


    /**
     * Gets the history value for this PublishedDossier.
     * 
     * @return history
     */
    public com.woodwing.enterprise.interfaces.services.pub.PublishHistory[] getHistory() {
        return history;
    }


    /**
     * Sets the history value for this PublishedDossier.
     * 
     * @param history
     */
    public void setHistory(com.woodwing.enterprise.interfaces.services.pub.PublishHistory[] history) {
        this.history = history;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof PublishedDossier)) return false;
        PublishedDossier other = (PublishedDossier) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.dossierID==null && other.getDossierID()==null) || 
             (this.dossierID!=null &&
              this.dossierID.equals(other.getDossierID()))) &&
            ((this.target==null && other.getTarget()==null) || 
             (this.target!=null &&
              this.target.equals(other.getTarget()))) &&
            ((this.publishedDate==null && other.getPublishedDate()==null) || 
             (this.publishedDate!=null &&
              this.publishedDate.equals(other.getPublishedDate()))) &&
            ((this.publishMessage==null && other.getPublishMessage()==null) || 
             (this.publishMessage!=null &&
              this.publishMessage.equals(other.getPublishMessage()))) &&
            ((this.online==null && other.getOnline()==null) || 
             (this.online!=null &&
              this.online.equals(other.getOnline()))) &&
            ((this.URL==null && other.getURL()==null) || 
             (this.URL!=null &&
              this.URL.equals(other.getURL()))) &&
            ((this.fields==null && other.getFields()==null) || 
             (this.fields!=null &&
              java.util.Arrays.equals(this.fields, other.getFields()))) &&
            ((this.history==null && other.getHistory()==null) || 
             (this.history!=null &&
              java.util.Arrays.equals(this.history, other.getHistory())));
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
        if (getDossierID() != null) {
            _hashCode += getDossierID().hashCode();
        }
        if (getTarget() != null) {
            _hashCode += getTarget().hashCode();
        }
        if (getPublishedDate() != null) {
            _hashCode += getPublishedDate().hashCode();
        }
        if (getPublishMessage() != null) {
            _hashCode += getPublishMessage().hashCode();
        }
        if (getOnline() != null) {
            _hashCode += getOnline().hashCode();
        }
        if (getURL() != null) {
            _hashCode += getURL().hashCode();
        }
        if (getFields() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getFields());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getFields(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getHistory() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getHistory());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getHistory(), i);
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
        new org.apache.axis.description.TypeDesc(PublishedDossier.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishedDossier"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("dossierID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DossierID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("target");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Target"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishTarget"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishedDate");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishedDate"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "dateTime"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishMessage");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishMessage"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "UserMessage"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("online");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Online"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("URL");
        elemField.setXmlName(new javax.xml.namespace.QName("", "URL"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("fields");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Fields"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "Field"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Field"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("history");
        elemField.setXmlName(new javax.xml.namespace.QName("", "History"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishHistory"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "PublishHistory"));
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
