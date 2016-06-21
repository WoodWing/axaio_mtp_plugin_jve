/**
 * UpdateObjectTargetsResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class UpdateObjectTargetsResponse  implements java.io.Serializable {
    private java.lang.String[] IDs;

    private com.woodwing.enterprise.interfaces.services.wfl.Target[] targets;

    public UpdateObjectTargetsResponse() {
    }

    public UpdateObjectTargetsResponse(
           java.lang.String[] IDs,
           com.woodwing.enterprise.interfaces.services.wfl.Target[] targets) {
           this.IDs = IDs;
           this.targets = targets;
    }


    /**
     * Gets the IDs value for this UpdateObjectTargetsResponse.
     * 
     * @return IDs
     */
    public java.lang.String[] getIDs() {
        return IDs;
    }


    /**
     * Sets the IDs value for this UpdateObjectTargetsResponse.
     * 
     * @param IDs
     */
    public void setIDs(java.lang.String[] IDs) {
        this.IDs = IDs;
    }


    /**
     * Gets the targets value for this UpdateObjectTargetsResponse.
     * 
     * @return targets
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Target[] getTargets() {
        return targets;
    }


    /**
     * Sets the targets value for this UpdateObjectTargetsResponse.
     * 
     * @param targets
     */
    public void setTargets(com.woodwing.enterprise.interfaces.services.wfl.Target[] targets) {
        this.targets = targets;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof UpdateObjectTargetsResponse)) return false;
        UpdateObjectTargetsResponse other = (UpdateObjectTargetsResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.IDs==null && other.getIDs()==null) || 
             (this.IDs!=null &&
              java.util.Arrays.equals(this.IDs, other.getIDs()))) &&
            ((this.targets==null && other.getTargets()==null) || 
             (this.targets!=null &&
              java.util.Arrays.equals(this.targets, other.getTargets())));
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
        if (getIDs() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getIDs());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getIDs(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getTargets() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getTargets());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getTargets(), i);
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
        new org.apache.axis.description.TypeDesc(UpdateObjectTargetsResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">UpdateObjectTargetsResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("IDs");
        elemField.setXmlName(new javax.xml.namespace.QName("", "IDs"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("targets");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Targets"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Target"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Target"));
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
