/**
 * ObjectTargetsInfo.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class ObjectTargetsInfo  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.BasicMetaData basicMetaData;

    private com.woodwing.enterprise.interfaces.services.wfl.Target[] targets;

    public ObjectTargetsInfo() {
    }

    public ObjectTargetsInfo(
           com.woodwing.enterprise.interfaces.services.wfl.BasicMetaData basicMetaData,
           com.woodwing.enterprise.interfaces.services.wfl.Target[] targets) {
           this.basicMetaData = basicMetaData;
           this.targets = targets;
    }


    /**
     * Gets the basicMetaData value for this ObjectTargetsInfo.
     * 
     * @return basicMetaData
     */
    public com.woodwing.enterprise.interfaces.services.wfl.BasicMetaData getBasicMetaData() {
        return basicMetaData;
    }


    /**
     * Sets the basicMetaData value for this ObjectTargetsInfo.
     * 
     * @param basicMetaData
     */
    public void setBasicMetaData(com.woodwing.enterprise.interfaces.services.wfl.BasicMetaData basicMetaData) {
        this.basicMetaData = basicMetaData;
    }


    /**
     * Gets the targets value for this ObjectTargetsInfo.
     * 
     * @return targets
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Target[] getTargets() {
        return targets;
    }


    /**
     * Sets the targets value for this ObjectTargetsInfo.
     * 
     * @param targets
     */
    public void setTargets(com.woodwing.enterprise.interfaces.services.wfl.Target[] targets) {
        this.targets = targets;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof ObjectTargetsInfo)) return false;
        ObjectTargetsInfo other = (ObjectTargetsInfo) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.basicMetaData==null && other.getBasicMetaData()==null) || 
             (this.basicMetaData!=null &&
              this.basicMetaData.equals(other.getBasicMetaData()))) &&
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
        if (getBasicMetaData() != null) {
            _hashCode += getBasicMetaData().hashCode();
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
        new org.apache.axis.description.TypeDesc(ObjectTargetsInfo.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectTargetsInfo"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("basicMetaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "BasicMetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "BasicMetaData"));
        elemField.setNillable(false);
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
