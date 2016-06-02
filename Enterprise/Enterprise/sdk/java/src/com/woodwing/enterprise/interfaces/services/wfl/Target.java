/**
 * Target.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class Target  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.PubChannel pubChannel;

    private com.woodwing.enterprise.interfaces.services.wfl.Issue issue;

    private com.woodwing.enterprise.interfaces.services.wfl.Edition[] editions;

    private com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty publishedDate;

    private java.lang.String publishedVersion;

    public Target() {
    }

    public Target(
           com.woodwing.enterprise.interfaces.services.wfl.PubChannel pubChannel,
           com.woodwing.enterprise.interfaces.services.wfl.Issue issue,
           com.woodwing.enterprise.interfaces.services.wfl.Edition[] editions,
           com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty publishedDate,
           java.lang.String publishedVersion) {
           this.pubChannel = pubChannel;
           this.issue = issue;
           this.editions = editions;
           this.publishedDate = publishedDate;
           this.publishedVersion = publishedVersion;
    }


    /**
     * Gets the pubChannel value for this Target.
     * 
     * @return pubChannel
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PubChannel getPubChannel() {
        return pubChannel;
    }


    /**
     * Sets the pubChannel value for this Target.
     * 
     * @param pubChannel
     */
    public void setPubChannel(com.woodwing.enterprise.interfaces.services.wfl.PubChannel pubChannel) {
        this.pubChannel = pubChannel;
    }


    /**
     * Gets the issue value for this Target.
     * 
     * @return issue
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Issue getIssue() {
        return issue;
    }


    /**
     * Sets the issue value for this Target.
     * 
     * @param issue
     */
    public void setIssue(com.woodwing.enterprise.interfaces.services.wfl.Issue issue) {
        this.issue = issue;
    }


    /**
     * Gets the editions value for this Target.
     * 
     * @return editions
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Edition[] getEditions() {
        return editions;
    }


    /**
     * Sets the editions value for this Target.
     * 
     * @param editions
     */
    public void setEditions(com.woodwing.enterprise.interfaces.services.wfl.Edition[] editions) {
        this.editions = editions;
    }


    /**
     * Gets the publishedDate value for this Target.
     * 
     * @return publishedDate
     */
    public com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty getPublishedDate() {
        return publishedDate;
    }


    /**
     * Sets the publishedDate value for this Target.
     * 
     * @param publishedDate
     */
    public void setPublishedDate(com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty publishedDate) {
        this.publishedDate = publishedDate;
    }


    /**
     * Gets the publishedVersion value for this Target.
     * 
     * @return publishedVersion
     */
    public java.lang.String getPublishedVersion() {
        return publishedVersion;
    }


    /**
     * Sets the publishedVersion value for this Target.
     * 
     * @param publishedVersion
     */
    public void setPublishedVersion(java.lang.String publishedVersion) {
        this.publishedVersion = publishedVersion;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Target)) return false;
        Target other = (Target) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.pubChannel==null && other.getPubChannel()==null) || 
             (this.pubChannel!=null &&
              this.pubChannel.equals(other.getPubChannel()))) &&
            ((this.issue==null && other.getIssue()==null) || 
             (this.issue!=null &&
              this.issue.equals(other.getIssue()))) &&
            ((this.editions==null && other.getEditions()==null) || 
             (this.editions!=null &&
              java.util.Arrays.equals(this.editions, other.getEditions()))) &&
            ((this.publishedDate==null && other.getPublishedDate()==null) || 
             (this.publishedDate!=null &&
              this.publishedDate.equals(other.getPublishedDate()))) &&
            ((this.publishedVersion==null && other.getPublishedVersion()==null) || 
             (this.publishedVersion!=null &&
              this.publishedVersion.equals(other.getPublishedVersion())));
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
        if (getPubChannel() != null) {
            _hashCode += getPubChannel().hashCode();
        }
        if (getIssue() != null) {
            _hashCode += getIssue().hashCode();
        }
        if (getEditions() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getEditions());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getEditions(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getPublishedDate() != null) {
            _hashCode += getPublishedDate().hashCode();
        }
        if (getPublishedVersion() != null) {
            _hashCode += getPublishedVersion().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Target.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Target"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pubChannel");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PubChannel"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PubChannel"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("issue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Issue"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Issue"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("editions");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Editions"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Edition"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Edition"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishedDate");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishedDate"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "dateTimeOrEmpty"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishedVersion");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishedVersion"));
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
