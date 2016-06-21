/**
 * ListArticleWorkspacesResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class ListArticleWorkspacesResponse  implements java.io.Serializable {
    private java.lang.String[] workspaces;

    public ListArticleWorkspacesResponse() {
    }

    public ListArticleWorkspacesResponse(
           java.lang.String[] workspaces) {
           this.workspaces = workspaces;
    }


    /**
     * Gets the workspaces value for this ListArticleWorkspacesResponse.
     * 
     * @return workspaces
     */
    public java.lang.String[] getWorkspaces() {
        return workspaces;
    }


    /**
     * Sets the workspaces value for this ListArticleWorkspacesResponse.
     * 
     * @param workspaces
     */
    public void setWorkspaces(java.lang.String[] workspaces) {
        this.workspaces = workspaces;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof ListArticleWorkspacesResponse)) return false;
        ListArticleWorkspacesResponse other = (ListArticleWorkspacesResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.workspaces==null && other.getWorkspaces()==null) || 
             (this.workspaces!=null &&
              java.util.Arrays.equals(this.workspaces, other.getWorkspaces())));
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
        if (getWorkspaces() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getWorkspaces());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getWorkspaces(), i);
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
        new org.apache.axis.description.TypeDesc(ListArticleWorkspacesResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">ListArticleWorkspacesResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("workspaces");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Workspaces"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setNillable(false);
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
