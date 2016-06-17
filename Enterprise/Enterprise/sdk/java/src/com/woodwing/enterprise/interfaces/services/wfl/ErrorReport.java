/**
 * ErrorReport.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class ErrorReport  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.ErrorReportEntity belongsTo;

    private com.woodwing.enterprise.interfaces.services.wfl.ErrorReportEntry[] entries;

    public ErrorReport() {
    }

    public ErrorReport(
           com.woodwing.enterprise.interfaces.services.wfl.ErrorReportEntity belongsTo,
           com.woodwing.enterprise.interfaces.services.wfl.ErrorReportEntry[] entries) {
           this.belongsTo = belongsTo;
           this.entries = entries;
    }


    /**
     * Gets the belongsTo value for this ErrorReport.
     * 
     * @return belongsTo
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ErrorReportEntity getBelongsTo() {
        return belongsTo;
    }


    /**
     * Sets the belongsTo value for this ErrorReport.
     * 
     * @param belongsTo
     */
    public void setBelongsTo(com.woodwing.enterprise.interfaces.services.wfl.ErrorReportEntity belongsTo) {
        this.belongsTo = belongsTo;
    }


    /**
     * Gets the entries value for this ErrorReport.
     * 
     * @return entries
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ErrorReportEntry[] getEntries() {
        return entries;
    }


    /**
     * Sets the entries value for this ErrorReport.
     * 
     * @param entries
     */
    public void setEntries(com.woodwing.enterprise.interfaces.services.wfl.ErrorReportEntry[] entries) {
        this.entries = entries;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof ErrorReport)) return false;
        ErrorReport other = (ErrorReport) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.belongsTo==null && other.getBelongsTo()==null) || 
             (this.belongsTo!=null &&
              this.belongsTo.equals(other.getBelongsTo()))) &&
            ((this.entries==null && other.getEntries()==null) || 
             (this.entries!=null &&
              java.util.Arrays.equals(this.entries, other.getEntries())));
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
        if (getBelongsTo() != null) {
            _hashCode += getBelongsTo().hashCode();
        }
        if (getEntries() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getEntries());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getEntries(), i);
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
        new org.apache.axis.description.TypeDesc(ErrorReport.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ErrorReport"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("belongsTo");
        elemField.setXmlName(new javax.xml.namespace.QName("", "BelongsTo"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ErrorReportEntity"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("entries");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Entries"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ErrorReportEntry"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ErrorReportEntry"));
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
