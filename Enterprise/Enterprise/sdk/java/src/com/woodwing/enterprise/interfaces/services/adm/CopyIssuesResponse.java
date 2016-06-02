/**
 * CopyIssuesResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class CopyIssuesResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.adm.Issue[] issues;

    public CopyIssuesResponse() {
    }

    public CopyIssuesResponse(
           com.woodwing.enterprise.interfaces.services.adm.Issue[] issues) {
           this.issues = issues;
    }


    /**
     * Gets the issues value for this CopyIssuesResponse.
     * 
     * @return issues
     */
    public com.woodwing.enterprise.interfaces.services.adm.Issue[] getIssues() {
        return issues;
    }


    /**
     * Sets the issues value for this CopyIssuesResponse.
     * 
     * @param issues
     */
    public void setIssues(com.woodwing.enterprise.interfaces.services.adm.Issue[] issues) {
        this.issues = issues;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof CopyIssuesResponse)) return false;
        CopyIssuesResponse other = (CopyIssuesResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.issues==null && other.getIssues()==null) || 
             (this.issues!=null &&
              java.util.Arrays.equals(this.issues, other.getIssues())));
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
        if (getIssues() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getIssues());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getIssues(), i);
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
        new org.apache.axis.description.TypeDesc(CopyIssuesResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CopyIssuesResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("issues");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Issues"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Issue"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Issue"));
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
