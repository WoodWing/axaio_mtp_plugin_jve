/**
 * GetDossierOrderResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public class GetDossierOrderResponse  implements java.io.Serializable {
    private java.lang.String[] dossierIDs;

    public GetDossierOrderResponse() {
    }

    public GetDossierOrderResponse(
           java.lang.String[] dossierIDs) {
           this.dossierIDs = dossierIDs;
    }


    /**
     * Gets the dossierIDs value for this GetDossierOrderResponse.
     * 
     * @return dossierIDs
     */
    public java.lang.String[] getDossierIDs() {
        return dossierIDs;
    }


    /**
     * Sets the dossierIDs value for this GetDossierOrderResponse.
     * 
     * @param dossierIDs
     */
    public void setDossierIDs(java.lang.String[] dossierIDs) {
        this.dossierIDs = dossierIDs;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetDossierOrderResponse)) return false;
        GetDossierOrderResponse other = (GetDossierOrderResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.dossierIDs==null && other.getDossierIDs()==null) || 
             (this.dossierIDs!=null &&
              java.util.Arrays.equals(this.dossierIDs, other.getDossierIDs())));
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
        if (getDossierIDs() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getDossierIDs());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getDossierIDs(), i);
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
        new org.apache.axis.description.TypeDesc(GetDossierOrderResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", ">GetDossierOrderResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("dossierIDs");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DossierIDs"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "String"));
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
