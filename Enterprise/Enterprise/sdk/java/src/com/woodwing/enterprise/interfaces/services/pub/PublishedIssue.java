/**
 * PublishedIssue.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public class PublishedIssue  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.pub.PublishTarget target;

    private java.lang.String version;

    private com.woodwing.enterprise.interfaces.services.pub.Field[] fields;

    private com.woodwing.enterprise.interfaces.services.pub.ReportMessage[] report;

    private java.util.Calendar publishedDate;

    private java.lang.String[] dossierOrder;

    public PublishedIssue() {
    }

    public PublishedIssue(
           com.woodwing.enterprise.interfaces.services.pub.PublishTarget target,
           java.lang.String version,
           com.woodwing.enterprise.interfaces.services.pub.Field[] fields,
           com.woodwing.enterprise.interfaces.services.pub.ReportMessage[] report,
           java.util.Calendar publishedDate,
           java.lang.String[] dossierOrder) {
           this.target = target;
           this.version = version;
           this.fields = fields;
           this.report = report;
           this.publishedDate = publishedDate;
           this.dossierOrder = dossierOrder;
    }


    /**
     * Gets the target value for this PublishedIssue.
     * 
     * @return target
     */
    public com.woodwing.enterprise.interfaces.services.pub.PublishTarget getTarget() {
        return target;
    }


    /**
     * Sets the target value for this PublishedIssue.
     * 
     * @param target
     */
    public void setTarget(com.woodwing.enterprise.interfaces.services.pub.PublishTarget target) {
        this.target = target;
    }


    /**
     * Gets the version value for this PublishedIssue.
     * 
     * @return version
     */
    public java.lang.String getVersion() {
        return version;
    }


    /**
     * Sets the version value for this PublishedIssue.
     * 
     * @param version
     */
    public void setVersion(java.lang.String version) {
        this.version = version;
    }


    /**
     * Gets the fields value for this PublishedIssue.
     * 
     * @return fields
     */
    public com.woodwing.enterprise.interfaces.services.pub.Field[] getFields() {
        return fields;
    }


    /**
     * Sets the fields value for this PublishedIssue.
     * 
     * @param fields
     */
    public void setFields(com.woodwing.enterprise.interfaces.services.pub.Field[] fields) {
        this.fields = fields;
    }


    /**
     * Gets the report value for this PublishedIssue.
     * 
     * @return report
     */
    public com.woodwing.enterprise.interfaces.services.pub.ReportMessage[] getReport() {
        return report;
    }


    /**
     * Sets the report value for this PublishedIssue.
     * 
     * @param report
     */
    public void setReport(com.woodwing.enterprise.interfaces.services.pub.ReportMessage[] report) {
        this.report = report;
    }


    /**
     * Gets the publishedDate value for this PublishedIssue.
     * 
     * @return publishedDate
     */
    public java.util.Calendar getPublishedDate() {
        return publishedDate;
    }


    /**
     * Sets the publishedDate value for this PublishedIssue.
     * 
     * @param publishedDate
     */
    public void setPublishedDate(java.util.Calendar publishedDate) {
        this.publishedDate = publishedDate;
    }


    /**
     * Gets the dossierOrder value for this PublishedIssue.
     * 
     * @return dossierOrder
     */
    public java.lang.String[] getDossierOrder() {
        return dossierOrder;
    }


    /**
     * Sets the dossierOrder value for this PublishedIssue.
     * 
     * @param dossierOrder
     */
    public void setDossierOrder(java.lang.String[] dossierOrder) {
        this.dossierOrder = dossierOrder;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof PublishedIssue)) return false;
        PublishedIssue other = (PublishedIssue) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.target==null && other.getTarget()==null) || 
             (this.target!=null &&
              this.target.equals(other.getTarget()))) &&
            ((this.version==null && other.getVersion()==null) || 
             (this.version!=null &&
              this.version.equals(other.getVersion()))) &&
            ((this.fields==null && other.getFields()==null) || 
             (this.fields!=null &&
              java.util.Arrays.equals(this.fields, other.getFields()))) &&
            ((this.report==null && other.getReport()==null) || 
             (this.report!=null &&
              java.util.Arrays.equals(this.report, other.getReport()))) &&
            ((this.publishedDate==null && other.getPublishedDate()==null) || 
             (this.publishedDate!=null &&
              this.publishedDate.equals(other.getPublishedDate()))) &&
            ((this.dossierOrder==null && other.getDossierOrder()==null) || 
             (this.dossierOrder!=null &&
              java.util.Arrays.equals(this.dossierOrder, other.getDossierOrder())));
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
        if (getTarget() != null) {
            _hashCode += getTarget().hashCode();
        }
        if (getVersion() != null) {
            _hashCode += getVersion().hashCode();
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
        if (getReport() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getReport());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getReport(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getPublishedDate() != null) {
            _hashCode += getPublishedDate().hashCode();
        }
        if (getDossierOrder() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getDossierOrder());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getDossierOrder(), i);
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
        new org.apache.axis.description.TypeDesc(PublishedIssue.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishedIssue"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("target");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Target"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishTarget"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("version");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Version"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("fields");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Fields"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "Field"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Field"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("report");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Report"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "ReportMessage"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ReportMessage"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishedDate");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishedDate"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "dateTime"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("dossierOrder");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DossierOrder"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "String"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
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
